<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Firm\Services\FirmApprovalSeeder;
use Illuminate\Console\Command;

/**
 * Backfill Owner-approval workflows on every existing active client-tenant.
 * Idempotent — re-running skips tenants that already have their five
 * sensitive-op workflows.
 *
 *   php artisan firm:seed-approval-workflows
 *   php artisan firm:seed-approval-workflows --dry-run
 */
class FirmSeedApprovalWorkflows extends Command
{
    protected $signature = 'firm:seed-approval-workflows {--dry-run}';
    protected $description = 'Seed default approval workflows on every existing client-tenant';

    public function handle(FirmApprovalSeeder $seeder): int
    {
        if ($this->option('dry-run')) {
            $this->info('[dry-run] Would scan every client-tenant and seed missing workflows.');
            return self::SUCCESS;
        }

        $this->info('Seeding approval workflows for all client-tenants…');
        $stats = $seeder->backfillAll();

        $this->newLine();
        $this->info(sprintf(
            'Scanned: %d  | Tenants with new workflows: %d  | Workflows created: %d',
            $stats['tenants'],
            $stats['tenants_with_new'],
            $stats['workflows_created'],
        ));

        if ($stats['workflows_created'] === 0) {
            $this->line('Nothing to do — every active client-tenant already has its workflows.');
        }

        return self::SUCCESS;
    }
}
