<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Services;

use App\Domain\Shared\Models\ApiUsageMeter;
use App\Domain\Subscription\Models\UsageRecord;
use Illuminate\Support\Collection;

/**
 * Usage metering for ERP. Stubbed during the muhasebi → kaabosh-erp split:
 * the original implementation tallied accounting artefacts (invoices, bills,
 * journal entries) that don't exist in the ERP product. To be rebuilt against
 * ERP metrics (employees, payroll runs, engagements, time entries, storage)
 * once the product lines stabilise.
 */
class UsageService
{
    public function recordUsage(?int $tenantId = null): ?UsageRecord
    {
        return null;
    }

    /**
     * @return array<string, int|null>
     */
    public function getUsage(?int $tenantId = null): array
    {
        return [
            'users' => 0,
            'storage_bytes' => 0,
            'api_calls' => (int) ApiUsageMeter::query()->sum('count'),
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
