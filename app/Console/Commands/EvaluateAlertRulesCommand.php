<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Notification\Services\AlertEngineService;
use App\Domain\Shared\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Console\Command;

class EvaluateAlertRulesCommand extends Command
{
    protected $signature = 'alerts:evaluate {--tenant= : Process only this tenant ID}';

    protected $description = 'Evaluate all active alert rules for each tenant and trigger notifications';

    public function handle(AlertEngineService $service): int
    {
        $tenantQuery = Tenant::where('status', TenantStatus::Active);

        if ($tenantId = $this->option('tenant')) {
            $tenantQuery->where('id', $tenantId);
        }

        $tenants = $tenantQuery->pluck('id');

        if ($tenants->isEmpty()) {
            $this->info('No active tenants found.');

            return self::SUCCESS;
        }

        $this->info("Evaluating alert rules for {$tenants->count()} tenants...");

        $totalTriggered = 0;

        foreach ($tenants as $tenantId) {
            $triggered = $service->evaluateAll($tenantId);
            $count = count($triggered);
            $totalTriggered += $count;

            if ($count > 0) {
                $this->line("  Tenant {$tenantId}: {$count} alert(s) triggered.");
            }
        }

        $this->info("Done. Total alerts triggered: {$totalTriggered}");

        return self::SUCCESS;
    }
}
