<?php

declare(strict_types=1);

namespace App\Domain\Admin\Services;

use App\Domain\Client\Models\Client;
use App\Domain\Document\Models\Document;
use App\Domain\Shared\Enums\TenantStatus;
use App\Domain\Shared\Enums\UserRole;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TenantManagementService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return Tenant::query()
            ->withCount('users')
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(
                isset($filters['search']),
                fn ($q) => $q->where(function ($q) use ($filters): void {
                    $q->where('name', 'ilike', "%{$filters['search']}%")
                        ->orWhere('email', 'ilike', "%{$filters['search']}%")
                        ->orWhere('slug', 'ilike', "%{$filters['search']}%");
                })
            )
            ->orderBy($filters['sort_by'] ?? 'created_at', $filters['sort_dir'] ?? 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * @return array<string, mixed>
     */
    public function getDetail(Tenant $tenant): array
    {
        // Active subscription
        $subscription = Subscription::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', ['active', 'trial', 'past_due'])
            ->with('plan')
            ->latest()
            ->first();

        // Subscription history (all)
        $subscriptionHistory = Subscription::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->with('plan')
            ->orderByDesc('created_at')
            ->get();

        // Live usage — computed from actual tables
        $usersCount = User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->count();

        $clientsCount = Client::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->count();

        $storageBytes = (int) Document::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->sum('size_bytes');

        $liveUsage = [
            'users_count' => $usersCount,
            'clients_count' => $clientsCount,
            'storage_bytes' => $storageBytes,
        ];

        // Plan limits
        $planLimits = $subscription?->plan?->limits ?? [];

        return [
            'tenant' => $tenant,
            'subscription' => $subscription,
            'subscription_history' => $subscriptionHistory,
            'users_count' => $usersCount,
            'usage' => $liveUsage,
            'plan_limits' => $planLimits,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Tenant $tenant, array $data): Tenant
    {
        $tenant->update($data);

        return $tenant->refresh();
    }

    public function suspend(Tenant $tenant): Tenant
    {
        $tenant->update(['status' => TenantStatus::Suspended]);

        return $tenant->refresh();
    }

    public function activate(Tenant $tenant): Tenant
    {
        $tenant->update(['status' => TenantStatus::Active]);

        return $tenant->refresh();
    }

    /**
     * @throws ValidationException
     */
    public function cancel(Tenant $tenant): Tenant
    {
        $tenant->update(['status' => TenantStatus::Cancelled]);

        // Cancel active subscription
        Subscription::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', ['active', 'trial'])
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => 'Cancelled by platform admin',
            ]);

        return $tenant->refresh();
    }

    /**
     * Generate an impersonation token for the tenant's admin user.
     *
     * @throws ValidationException
     */
    public function impersonate(Tenant $tenant): string
    {
        $admin = User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('role', UserRole::Admin)
            ->where('is_active', true)
            ->first();

        if (! $admin) {
            throw ValidationException::withMessages([
                'tenant' => [
                    'No active admin user found for this tenant.',
                    'لا يوجد مستخدم مدير نشط لهذا الحساب.',
                ],
            ]);
        }

        $token = $admin->createToken(
            'impersonation-'.auth()->id(),
            ['*'],
        );

        Log::channel('stack')->warning('Admin impersonation', [
            'super_admin_id' => auth()->id(),
            'super_admin_email' => auth()->user()?->email,
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'impersonated_user_id' => $admin->id,
            'impersonated_user_email' => $admin->email,
            'ip' => request()->ip(),
        ]);

        activity('admin')
            ->performedOn($tenant)
            ->causedBy(auth()->user())
            ->withProperties([
                'impersonated_user_id' => $admin->id,
                'impersonated_user_email' => $admin->email,
                'ip' => request()->ip(),
            ])
            ->log('Impersonated tenant admin');

        return $token->plainTextToken;
    }
}
