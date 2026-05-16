<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Firm;

use App\Domain\Shared\Enums\FirmRole;
use App\Domain\Shared\Enums\TenantType;
use App\Domain\Tenant\Models\Tenant;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Assign / unassign firm staff to specific client-tenants. Owner-only ops:
 * assignment is the access-control lever, so only the firm Owner can change
 * it. The list endpoint is open to any firm member with manager-level access.
 *
 * Firm-wide roles (Owner/Partner/Manager) aren't "assigned" — they always
 * have access. Only Accountant/Viewer benefit from explicit assignment;
 * the UI marks firm-wide roles separately.
 */
class FirmAssignmentsController extends Controller
{
    /**
     * List every firm staff member + whether they currently have access to
     * the given client-tenant. Used by the "Assigned staff" slideover on
     * the Companies page.
     */
    public function index(Request $request, int $tenantId): JsonResponse
    {
        $user = $request->user();
        if (! $user?->firm_id) {
            return response()->json(['message' => 'User is not attached to a firm.'], Response::HTTP_FORBIDDEN);
        }

        if (! in_array($user->firm_role, [FirmRole::Owner, FirmRole::Partner, FirmRole::Manager], true)) {
            return response()->json(['message' => 'Insufficient firm-role.'], Response::HTTP_FORBIDDEN);
        }

        $tenant = Tenant::where('firm_id', $user->firm_id)
            ->where('type', TenantType::ClientBooks->value)
            ->find($tenantId);

        if (! $tenant) {
            return response()->json(['message' => 'Client not found.'], Response::HTTP_NOT_FOUND);
        }

        $staff = User::where('firm_id', $user->firm_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'firm_role']);

        $assignments = DB::table('firm_user_tenant')
            ->where('tenant_id', $tenant->id)
            ->get(['user_id', 'bypass_approvals'])
            ->keyBy('user_id');

        return response()->json([
            'data' => [
                'tenant' => [
                    'id'   => $tenant->id,
                    'name' => $tenant->name,
                ],
                'staff' => $staff->map(function (User $s) use ($assignments): array {
                    $role = $s->firm_role;
                    $firmWide = $role instanceof FirmRole && $role->hasFirmWideAccess();
                    $row = $assignments[$s->id] ?? null;
                    return [
                        'id'               => $s->id,
                        'name'             => $s->name,
                        'email'            => $s->email,
                        'firm_role'        => $role?->value,
                        'firm_wide'        => $firmWide,
                        'assigned'         => $firmWide || $row !== null,
                        'assignable'       => ! $firmWide,
                        'bypass_approvals' => $firmWide ? true : (bool) ($row->bypass_approvals ?? false),
                    ];
                })->values(),
            ],
        ]);
    }

    /**
     * Toggle the bypass_approvals flag for a (user, tenant) assignment.
     * Owner-only. Setting it true means the staff member skips firm-owner
     * approval gates on this tenant.
     */
    public function toggleBypass(Request $request, int $tenantId, int $userId): JsonResponse
    {
        $actor = $request->user();
        if (! $actor?->firm_id || $actor->firm_role !== FirmRole::Owner) {
            return response()->json(['message' => 'Only the firm owner can change approval bypass.'], Response::HTTP_FORBIDDEN);
        }

        $tenant = Tenant::where('firm_id', $actor->firm_id)
            ->where('type', TenantType::ClientBooks->value)
            ->find($tenantId);
        if (! $tenant) {
            return response()->json(['message' => 'Client not found.'], Response::HTTP_NOT_FOUND);
        }

        $staff = User::where('firm_id', $actor->firm_id)->find($userId);
        if (! $staff) {
            return response()->json(['message' => 'Staff member not found.'], Response::HTTP_NOT_FOUND);
        }

        $role = $staff->firm_role;
        if ($role instanceof FirmRole && $role->hasFirmWideAccess()) {
            return response()->json([
                'message' => 'Firm-wide roles already bypass approvals by virtue of their role.',
                'code'    => 'firm_wide_role',
            ], Response::HTTP_CONFLICT);
        }

        $data = $request->validate([
            'bypass_approvals' => ['required', 'boolean'],
        ]);

        $existing = DB::table('firm_user_tenant')
            ->where('user_id', $staff->id)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (! $existing) {
            return response()->json([
                'message' => 'Staff must be assigned to this client before toggling approval bypass.',
                'code'    => 'not_assigned',
            ], Response::HTTP_CONFLICT);
        }

        $previous = (bool) $existing->bypass_approvals;
        $next     = (bool) $data['bypass_approvals'];

        DB::table('firm_user_tenant')
            ->where('id', $existing->id)
            ->update([
                'bypass_approvals' => $next,
                'updated_at'       => now(),
            ]);

        // Audit trail — trust toggles are security-relevant. Log old/new
        // plus actor + IP. Spatie ActivityLog picks this up and serves it
        // through the existing audit-log SPA page.
        if ($previous !== $next) {
            activity('firm_assignment')
                ->performedOn($staff)
                ->causedBy($actor)
                ->withProperties([
                    'tenant_id'   => $tenant->id,
                    'tenant_name' => $tenant->name,
                    'user_id'     => $staff->id,
                    'user_email'  => $staff->email,
                    'old'         => ['bypass_approvals' => $previous],
                    'new'         => ['bypass_approvals' => $next],
                    'ip'          => $request->ip(),
                ])
                ->log($next ? 'bypass_approvals_granted' : 'bypass_approvals_revoked');
        }

        return response()->json(['data' => [
            'user_id'          => $staff->id,
            'tenant_id'        => $tenant->id,
            'bypass_approvals' => $next,
        ]]);
    }

    public function assign(Request $request, int $tenantId, int $userId): JsonResponse
    {
        $actor = $request->user();
        if (! $actor?->firm_id || $actor->firm_role !== FirmRole::Owner) {
            return response()->json(['message' => 'Only the firm owner can change client assignments.'], Response::HTTP_FORBIDDEN);
        }

        $tenant = Tenant::where('firm_id', $actor->firm_id)
            ->where('type', TenantType::ClientBooks->value)
            ->find($tenantId);
        if (! $tenant) {
            return response()->json(['message' => 'Client not found.'], Response::HTTP_NOT_FOUND);
        }

        $staff = User::where('firm_id', $actor->firm_id)->find($userId);
        if (! $staff) {
            return response()->json(['message' => 'Staff member not found.'], Response::HTTP_NOT_FOUND);
        }

        // Firm-wide roles don't need (and can't be) assigned — they already
        // have access. Reject with a clear message.
        $role = $staff->firm_role;
        if ($role instanceof FirmRole && $role->hasFirmWideAccess()) {
            return response()->json([
                'message' => 'This staff member already has firm-wide access by virtue of their role; explicit assignment is not needed.',
                'code'    => 'firm_wide_role',
            ], Response::HTTP_CONFLICT);
        }

        DB::table('firm_user_tenant')->updateOrInsert(
            ['user_id' => $staff->id, 'tenant_id' => $tenant->id],
            [
                'firm_id'             => $actor->firm_id,
                'assigned_by_user_id' => $actor->id,
                'updated_at'          => now(),
                'created_at'          => now(),
            ],
        );

        return response()->json(['data' => ['user_id' => $staff->id, 'tenant_id' => $tenant->id, 'assigned' => true]]);
    }

    public function unassign(Request $request, int $tenantId, int $userId): JsonResponse
    {
        $actor = $request->user();
        if (! $actor?->firm_id || $actor->firm_role !== FirmRole::Owner) {
            return response()->json(['message' => 'Only the firm owner can change client assignments.'], Response::HTTP_FORBIDDEN);
        }

        $tenant = Tenant::where('firm_id', $actor->firm_id)
            ->where('type', TenantType::ClientBooks->value)
            ->find($tenantId);
        if (! $tenant) {
            return response()->json(['message' => 'Client not found.'], Response::HTTP_NOT_FOUND);
        }

        DB::table('firm_user_tenant')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenant->id)
            ->delete();

        return response()->json(['data' => ['user_id' => $userId, 'tenant_id' => $tenant->id, 'assigned' => false]]);
    }
}
