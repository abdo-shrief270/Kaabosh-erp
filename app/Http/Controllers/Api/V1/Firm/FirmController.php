<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Firm;

use App\Domain\Firm\Models\Firm;
use App\Domain\Firm\Services\ClientTierService;
use App\Domain\Firm\Services\FirmApprovalSeeder;
use App\Domain\Shared\Enums\ClientTier;
use App\Domain\Shared\Enums\FirmRole;
use App\Domain\Shared\Enums\TenantStatus;
use App\Domain\Shared\Enums\TenantType;
use App\Domain\Tenant\Models\Tenant;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class FirmController extends Controller
{
    public function __construct(
        private readonly ClientTierService $tierService,
        private readonly FirmApprovalSeeder $approvalSeeder,
    ) {}

    /**
     * Return the authenticated user's firm with its tenants, ready for the
     * SPA's tenant switcher.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user?->firm_id) {
            return response()->json(['message' => 'User is not attached to a firm.'], Response::HTTP_NOT_FOUND);
        }

        $firm = Firm::with(['tenants' => function ($q): void {
            $q->select('id', 'firm_id', 'name', 'slug', 'type', 'client_tier', 'status', 'tax_id')
              ->orderByRaw("CASE WHEN type = 'firm_books' THEN 0 ELSE 1 END")
              ->orderBy('name');
        }])->find($user->firm_id);

        // For non-firm-wide roles, narrow the tenants list to what the user is
        // actually allowed to see. The firm-books tenant is always visible.
        $visible = $firm->tenants->filter(fn (Tenant $t): bool => $user->canAccessTenant($t));

        return response()->json([
            'data' => [
                'id'                => $firm->id,
                'name'              => $firm->name,
                'slug'              => $firm->slug,
                'subscription_tier' => $firm->subscription_tier,
                'tenants'           => $visible->map(fn (Tenant $t): array => [
                    'id'          => $t->id,
                    'name'        => $t->name,
                    'slug'        => $t->slug,
                    'type'        => $t->type?->value,
                    'client_tier' => $t->client_tier?->value,
                    'status'      => $t->status?->value,
                    'tax_id'      => $t->tax_id,
                ])->values(),
            ],
        ]);
    }

    /**
     * Add a new client-tenant under the firm. Only owners/partners/managers
     * can onboard new clients.
     */
    public function storeClientTenant(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user?->firm_id) {
            return response()->json(['message' => 'User is not attached to a firm.'], Response::HTTP_FORBIDDEN);
        }

        $allowedRoles = [FirmRole::Owner, FirmRole::Partner, FirmRole::Manager];
        if (! in_array($user->firm_role, $allowedRoles, true)) {
            return response()->json(['message' => 'Insufficient firm-role to onboard clients.'], Response::HTTP_FORBIDDEN);
        }

        $data = $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'tax_id'              => ['nullable', 'string', 'max:20'],
            'commercial_register' => ['nullable', 'string', 'max:30'],
            'email'               => ['nullable', 'email', 'max:255'],
            'phone'               => ['nullable', 'string', 'max:20'],
            'address'             => ['nullable', 'string'],
            'city'                => ['nullable', 'string', 'max:100'],
            'client_tier'         => ['required', 'string', 'in:micro,small,standard,large'],
        ]);

        $tenant = DB::transaction(function () use ($data, $user): Tenant {
            $tenant = Tenant::create([
                'firm_id'             => $user->firm_id,
                'type'                => TenantType::ClientBooks->value,
                'client_tier'         => $data['client_tier'],
                'name'                => $data['name'],
                'slug'                => $this->uniqueSlug($data['name']),
                'tax_id'              => $data['tax_id'] ?? null,
                'commercial_register' => $data['commercial_register'] ?? null,
                'email'               => $data['email'] ?? null,
                'phone'               => $data['phone'] ?? null,
                'address'             => $data['address'] ?? null,
                'city'                => $data['city'] ?? null,
                'status'              => TenantStatus::Active->value,
            ]);

            $this->scaffoldClientTenant($tenant);

            return $tenant;
        });

        return response()->json([
            'data' => [
                'id'          => $tenant->id,
                'name'        => $tenant->name,
                'slug'        => $tenant->slug,
                'type'        => $tenant->type?->value,
                'client_tier' => $tenant->client_tier?->value,
                'status'      => $tenant->status?->value,
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * The actual "switch" is implicit: the SPA simply changes which tenant id
     * it sends via X-Tenant header on subsequent requests. This endpoint exists
     * to validate access + return tenant metadata in one call.
     */
    /**
     * Tier usage for a client-tenant — current month's transaction count
     * vs. tier limit. Used by the SPA to show a tier chip and upgrade
     * prompts when approaching the cap.
     */
    public function tierUsage(Request $request, int $tenantId): JsonResponse
    {
        $user = $request->user();
        if (! $user?->firm_id) {
            return response()->json(['message' => 'User is not attached to a firm.'], Response::HTTP_FORBIDDEN);
        }

        $tenant = Tenant::where('firm_id', $user->firm_id)->find($tenantId);
        if (! $tenant) {
            return response()->json(['message' => 'Tenant not found in your firm.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => $this->tierService->usage($tenant),
        ]);
    }

    /**
     * GET the firm's own identity (name, tax ID, address, contacts). Distinct
     * from `show()` which returns the tenant list — this is just the firm
     * record for the settings page.
     */
    public function settings(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user?->firm_id) {
            return response()->json(['message' => 'User is not attached to a firm.'], Response::HTTP_FORBIDDEN);
        }

        $firm = Firm::find($user->firm_id);

        return response()->json([
            'data' => [
                'id'                  => $firm->id,
                'name'                => $firm->name,
                'slug'                => $firm->slug,
                'email'               => $firm->email,
                'phone'               => $firm->phone,
                'tax_id'              => $firm->tax_id,
                'commercial_register' => $firm->commercial_register,
                'address'             => $firm->address,
                'city'                => $firm->city,
                'subscription_tier'   => $firm->subscription_tier,
            ],
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user?->firm_id) {
            return response()->json(['message' => 'User is not attached to a firm.'], Response::HTTP_FORBIDDEN);
        }

        if (! in_array($user->firm_role, [FirmRole::Owner, FirmRole::Partner], true)) {
            return response()->json(['message' => 'Only owners or partners can edit firm settings.'], Response::HTTP_FORBIDDEN);
        }

        $data = $request->validate([
            'name'                => ['sometimes', 'string', 'max:255'],
            'email'               => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone'               => ['sometimes', 'nullable', 'string', 'max:20'],
            'tax_id'              => ['sometimes', 'nullable', 'string', 'max:20'],
            'commercial_register' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address'             => ['sometimes', 'nullable', 'string'],
            'city'                => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $firm = Firm::find($user->firm_id);
        $firm->fill($data)->save();

        return $this->settings($request);
    }

    public function switchTenant(Request $request, int $tenantId): JsonResponse
    {
        $user = $request->user();
        if (! $user?->firm_id) {
            return response()->json(['message' => 'User is not attached to a firm.'], Response::HTTP_FORBIDDEN);
        }

        $tenant = Tenant::where('firm_id', $user->firm_id)->find($tenantId);
        if (! $tenant) {
            return response()->json(['message' => 'Tenant not found in your firm.'], Response::HTTP_NOT_FOUND);
        }

        if (! $tenant->status?->isAccessible()) {
            return response()->json([
                'message' => 'Tenant is not accessible. Status: '.$tenant->status->label(),
            ], Response::HTTP_FORBIDDEN);
        }

        return response()->json([
            'data' => [
                'id'          => $tenant->id,
                'name'        => $tenant->name,
                'slug'        => $tenant->slug,
                'type'        => $tenant->type?->value,
                'client_tier' => $tenant->client_tier?->value,
                'status'      => $tenant->status?->value,
            ],
        ]);
    }

    /**
     * Scaffold a newly-created client-tenant so it's usable immediately.
     * Best-effort: a failure logs and continues. (The accounting CoA /
     * fiscal-year / ETA scaffolds were removed with the muhasebi domains.)
     */
    private function scaffoldClientTenant(Tenant $tenant): void
    {
        try {
            $this->approvalSeeder->seedFor($tenant);
        } catch (\Throwable $e) {
            Log::warning('client-tenant approval seed failed', [
                'tenant_id' => $tenant->id,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'tenant-'.Str::random(6);
        $slug = $base;
        $i = 0;
        while (Tenant::where('slug', $slug)->exists()) {
            $i++;
            $slug = $base.'-'.$i;
        }
        return $slug;
    }
}
