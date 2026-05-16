<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Services;

use App\Domain\Client\Models\Client;
use App\Domain\Document\Models\Document;
use App\Domain\Shared\Models\ApiUsageMeter;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\UsageRecord;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Usage metering for kaabosh-erp.
 *
 * Reports the tenant's consumption of the metered resources that exist in
 * the ERP product — active users, clients, stored documents, document
 * storage bytes, and API calls — against the active subscription plan's
 * `limits` map. The muhasebi accounting metrics (invoices/bills/journal
 * entries/bank imports) were dropped with the product split.
 *
 * Each dimensioned metric is returned as:
 *   ['current' => int, 'limit' => int, 'percent' => int,
 *    'percentage' => int, 'exceeded' => bool]
 * `limit` carries the raw plan value: `-1` = unlimited, `0` = uncapped/unset
 * (consumers must treat both as "do not warn / do not block").
 */
class UsageService
{
    public function recordUsage(?int $tenantId = null): ?UsageRecord
    {
        return null;
    }

    /**
     * Current usage vs. the active plan's limits for the given (or current)
     * tenant. Queries run without the BelongsToTenant scope so they work
     * from console/sweep contexts where no tenant is bound.
     *
     * @return array<string, array<string, int|bool>>
     */
    public function getUsage(?int $tenantId = null): array
    {
        $tenantId ??= app()->bound('tenant.id') ? app('tenant.id') : null;
        if (! $tenantId) {
            return [];
        }

        $plan = Subscription::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', [SubscriptionStatus::Trial->value, SubscriptionStatus::Active->value])
            ->latest('id')
            ->with('plan')
            ->first()?->plan;

        $limits = is_array($plan?->limits) ? $plan->limits : [];

        $users = User::query()->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->count();

        $clients = Client::query()->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->count();

        $documents = Document::query()->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->count();

        $storageBytes = (int) Document::query()->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->sum('size_bytes');

        $apiCalls = (int) ApiUsageMeter::query()->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [now()->startOfMonth()->toDateString(), now()->toDateString()])
            ->sum('api_calls');

        $storage = $this->metric($storageBytes, (int) ($limits['max_storage_bytes'] ?? 0));
        $storage['limit_bytes'] = $storage['limit'];

        return [
            'users' => $this->metric($users, (int) ($limits['max_users'] ?? 0)),
            'clients' => $this->metric($clients, (int) ($limits['max_clients'] ?? 0)),
            'documents' => $this->metric($documents, (int) ($limits['max_documents'] ?? 0)),
            'storage' => $storage,
            'api_calls' => $this->metric($apiCalls, (int) ($limits['max_api_calls_per_month'] ?? 0)),
        ];
    }

    /**
     * Build a single metric. `percent` is 0 for unlimited (-1) or unset (0)
     * caps so neither the usage-warning sweep nor the limit middleware acts
     * on an uncapped resource.
     *
     * @return array<string, int|bool>
     */
    private function metric(int $current, int $limit): array
    {
        $percent = $limit > 0 ? (int) floor($current / $limit * 100) : 0;

        return [
            'current' => $current,
            'limit' => $limit,
            'percent' => $percent,
            'percentage' => $percent,
            'exceeded' => $limit > 0 && $current >= $limit,
        ];
    }

    public function checkLimit(string $resource): bool
    {
        return true;
    }

    public function enforceLimit(string $resource): void
    {
    }

    /**
     * @return Collection<int, mixed>
     */
    public function getUsageHistory(int $days = 30, ?int $tenantId = null): Collection
    {
        return collect();
    }
}
