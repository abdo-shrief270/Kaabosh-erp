<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Subscription\Services\UsageService;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Console\Command;

class RecordUsageCommand extends Command
{
    protected $signature = 'usage:record';

    protected $description = 'Record daily usage snapshot for all active tenants';

    public function handle(UsageService $usageService): int
    {
        $tenants = Tenant::query()->accessible()->get();
        $count = 0;

        foreach ($tenants as $tenant) {
            try {
                $usageService->recordUsage($tenant->id);
                $count++;
            } catch (\Throwable $e) {
                $this->warn("Failed for tenant {$tenant->slug}: {$e->getMessage()}");
            }
        }

        $this->info("Recorded usage for {$count} tenants.");

        return self::SUCCESS;
    }
}
