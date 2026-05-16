<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Firm;

use App\Domain\Shared\Enums\FirmRole;
use App\Domain\Shared\Enums\TenantType;
use App\Domain\Shared\Enums\UserRole;
use App\Domain\Tenant\Models\Tenant;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Firm-level staff management. Staff are users with a `firm_id`; they get
 * access to every tenant in the firm and have a `firm_role` (owner, partner,
 * manager, accountant, viewer) controlling who can do firm-level operations
 * (onboard clients, change billing, deactivate staff).
 *
 * Permissions are role-gated: only owners can manage staff. Partners can
 * onboard clients but not other staff.
 */
class FirmStaffController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user?->firm_id) {
            return response()->json(['message' => 'User is not attached to a firm.'], Response::HTTP_FORBIDDEN);
        }

        $staff = User::where('firm_id', $user->firm_id)
            ->orderByRaw("CASE firm_role
                WHEN 'owner' THEN 1
                WHEN 'partner' THEN 2
                WHEN 'manager' THEN 3
                WHEN 'accountant' THEN 4
                WHEN 'viewer' THEN 5
                ELSE 6 END")
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'firm_role', 'is_active', 'last_login_at', 'two_factor_enabled', 'created_at']);

        // Total client-tenants in the firm (used as the "All" denominator
        // for firm-wide roles).
        $totalClients = Tenant::where('firm_id', $user->firm_id)
            ->where('type', TenantType::ClientBooks->value)
            ->count();

        // Per-user assignment count (only meaningful for narrow roles).
        $assignedCounts = DB::table('firm_user_tenant')
            ->where('firm_id', $user->firm_id)
            ->selectRaw('user_id, COUNT(*) AS c')
            ->groupBy('user_id')
            ->pluck('c', 'user_id');

        return response()->json([
            'data' => $staff->map(function (User $u) use ($assignedCounts, $totalClients): array {
                $role = $u->firm_role;
                $firmWide = $role instanceof FirmRole && $role->hasFirmWideAccess();
                return [
                    'id'                    => $u->id,
                    'name'                  => $u->name,
                    'email'                 => $u->email,
                    'phone'                 => $u->phone,
                    'firm_role'             => $role?->value,
                    'is_active'             => $u->is_active,
                    'last_login_at'         => $u->last_login_at?->toIso8601String(),
                    'two_factor_enabled'    => (bool) $u->two_factor_enabled,
                    'firm_wide_access'      => $firmWide,
                    'assigned_clients_count'=> $firmWide ? $totalClients : (int) ($assignedCounts[$u->id] ?? 0),
                    'created_at'            => $u->created_at?->toIso8601String(),
                ];
            })->values(),
            'meta' => ['total_clients' => $totalClients],
        ]);
    }

    /**
     * For a given staff member, list every client-tenant in the firm with a
     * boolean indicating whether they currently have access. Mirrors the
     * Companies-page assignments view from the other angle.
     */
    public function assignments(Request $request, int $staffId): JsonResponse
    {
        $user = $request->user();
        if (! $user?->firm_id) {
            return response()->json(['message' => 'User is not attached to a firm.'], Response::HTTP_FORBIDDEN);
        }

        if (! in_array($user->firm_role, [FirmRole::Owner, FirmRole::Partner, FirmRole::Manager], true)) {
            return response()->json(['message' => 'Insufficient firm-role.'], Response::HTTP_FORBIDDEN);
        }

        $staff = User::where('firm_id', $user->firm_id)->find($staffId);
        if (! $staff) {
            return response()->json(['message' => 'Staff member not found.'], Response::HTTP_NOT_FOUND);
        }

        $clients = Tenant::where('firm_id', $user->firm_id)
            ->where('type', TenantType::ClientBooks->value)
            ->orderBy('name')
            ->get(['id', 'name', 'client_tier', 'status']);

        $assignedIds = DB::table('firm_user_tenant')
            ->where('user_id', $staff->id)
            ->pluck('tenant_id')
            ->all();
        $assignedSet = array_flip($assignedIds);

        $role = $staff->firm_role;
        $firmWide = $role instanceof FirmRole && $role->hasFirmWideAccess();

        return response()->json([
            'data' => [
                'staff' => [
                    'id'        => $staff->id,
                    'name'      => $staff->name,
                    'email'     => $staff->email,
                    'firm_role' => $role?->value,
                    'firm_wide' => $firmWide,
                ],
                'clients' => $clients->map(fn (Tenant $t): array => [
                    'id'          => $t->id,
                    'name'        => $t->name,
                    'client_tier' => $t->client_tier?->value,
                    'status'      => $t->status?->value,
                    'assigned'    => $firmWide || isset($assignedSet[$t->id]),
                ])->values(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user?->firm_id) {
            return response()->json(['message' => 'User is not attached to a firm.'], Response::HTTP_FORBIDDEN);
        }

        if ($user->firm_role !== FirmRole::Owner) {
            return response()->json(['message' => 'Only the firm owner can invite staff.'], Response::HTTP_FORBIDDEN);
        }

        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'     => ['nullable', 'string', 'max:20'],
            'firm_role' => ['required', 'string', 'in:owner,partner,manager,accountant,viewer'],
        ]);

        // Anchor the new staff to the firm's own books as their "home" tenant.
        // They can switch to any client-tenant via the topbar switcher.
        $homeTenant = Tenant::where('firm_id', $user->firm_id)
            ->where('type', 'firm_books')
            ->first();

        $tempPassword = Str::random(16);
        $staff = User::create([
            'firm_id'   => $user->firm_id,
            'firm_role' => $data['firm_role'],
            'tenant_id' => $homeTenant?->id,
            'name'      => $data['name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'] ?? null,
            'password'  => Hash::make($tempPassword),
            'role'      => UserRole::Client->value, // legacy role; firm_role is the source of truth
            'is_active' => true,
        ]);

        return response()->json([
            'data' => [
                'id'        => $staff->id,
                'name'      => $staff->name,
                'email'     => $staff->email,
                'firm_role' => $staff->firm_role?->value,
            ],
            'meta' => [
                'temp_password' => $tempPassword, // shown once; the SPA must copy + share securely
                'note'          => 'Share the temporary password with the new staff member through a secure channel; they should change it on first login.',
            ],
        ], Response::HTTP_CREATED);
    }

    public function update(Request $request, int $staffId): JsonResponse
    {
        $user = $request->user();
        if (! $user?->firm_id) {
            return response()->json(['message' => 'User is not attached to a firm.'], Response::HTTP_FORBIDDEN);
        }

        if ($user->firm_role !== FirmRole::Owner) {
            return response()->json(['message' => 'Only the firm owner can modify staff.'], Response::HTTP_FORBIDDEN);
        }

        $staff = User::where('firm_id', $user->firm_id)->find($staffId);
        if (! $staff) {
            return response()->json(['message' => 'Staff member not found.'], Response::HTTP_NOT_FOUND);
        }

        $data = $request->validate([
            'name'      => ['sometimes', 'string', 'max:255'],
            'phone'     => ['sometimes', 'nullable', 'string', 'max:20'],
            'firm_role' => ['sometimes', 'string', 'in:owner,partner,manager,accountant,viewer'],
        ]);

        // Block demoting the last owner — otherwise the firm has nobody who
        // can manage staff or billing.
        if (
            isset($data['firm_role'])
            && $staff->firm_role === FirmRole::Owner
            && $data['firm_role'] !== 'owner'
        ) {
            $otherOwners = User::where('firm_id', $user->firm_id)
                ->where('firm_role', 'owner')
                ->where('id', '!=', $staff->id)
                ->count();

            if ($otherOwners === 0) {
                return response()->json(['message' => 'Cannot demote the only firm owner.'], Response::HTTP_CONFLICT);
            }
        }

        $staff->fill($data)->save();

        return response()->json([
            'data' => [
                'id'        => $staff->id,
                'name'      => $staff->name,
                'firm_role' => $staff->firm_role?->value,
                'is_active' => $staff->is_active,
            ],
        ]);
    }

    public function deactivate(Request $request, int $staffId): JsonResponse
    {
        $user = $request->user();
        if (! $user?->firm_id) {
            return response()->json(['message' => 'User is not attached to a firm.'], Response::HTTP_FORBIDDEN);
        }

        if ($user->firm_role !== FirmRole::Owner) {
            return response()->json(['message' => 'Only the firm owner can deactivate staff.'], Response::HTTP_FORBIDDEN);
        }

        if ($user->id === $staffId) {
            return response()->json(['message' => 'You cannot deactivate yourself.'], Response::HTTP_CONFLICT);
        }

        $staff = User::where('firm_id', $user->firm_id)->find($staffId);
        if (! $staff) {
            return response()->json(['message' => 'Staff member not found.'], Response::HTTP_NOT_FOUND);
        }

        if ($staff->firm_role === FirmRole::Owner) {
            $otherOwners = User::where('firm_id', $user->firm_id)
                ->where('firm_role', 'owner')
                ->where('id', '!=', $staff->id)
                ->count();

            if ($otherOwners === 0) {
                return response()->json(['message' => 'Cannot deactivate the only firm owner.'], Response::HTTP_CONFLICT);
            }
        }

        $staff->is_active = false;
        $staff->save();

        return response()->json(['data' => ['id' => $staff->id, 'is_active' => false]]);
    }

    public function reactivate(Request $request, int $staffId): JsonResponse
    {
        $user = $request->user();
        if (! $user?->firm_id || $user->firm_role !== FirmRole::Owner) {
            return response()->json(['message' => 'Only the firm owner can reactivate staff.'], Response::HTTP_FORBIDDEN);
        }

        $staff = User::where('firm_id', $user->firm_id)->find($staffId);
        if (! $staff) {
            return response()->json(['message' => 'Staff member not found.'], Response::HTTP_NOT_FOUND);
        }

        $staff->is_active = true;
        $staff->save();

        return response()->json(['data' => ['id' => $staff->id, 'is_active' => true]]);
    }
}
