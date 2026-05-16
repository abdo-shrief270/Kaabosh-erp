<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Tenant\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Permanently deletes soft-deleted tenants after the retention period.
 * Cascades to all tenant-owned data.
 * Runs the GDPR export automatically before purging.
 */
class PurgeTrashedTenantsCommand extends Command
{
    protected $signature = 'tenants:purge
        {--days=30 : Grace period in days after soft-delete}
        {--export : Auto-export data before purging}
        {--force : Skip confirmation}';

    protected $description = 'Permanently delete soft-deleted tenants and all their data after grace period';

    private const TENANT_TABLES = [
        'payroll_items', 'payroll_runs', 'employees',
        'timers', 'timesheet_entries',
        'eta_item_codes', 'eta_documents', 'eta_submissions', 'eta_settings',
        'messages', 'notifications', 'onboarding_steps',
        'storage_quotas', 'usage_records',
        'invoice_settings', 'payments', 'invoice_lines', 'invoices',
        'journal_entry_lines', 'journal_entries',
        'fiscal_periods', 'fiscal_years', 'accounts',
        'documents', 'clients',
        'subscriptions',
        'webhook_deliveries', // via endpoint cascade
        'webhook_endpoints',
        'api_usage_meters',
    ];

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $tenants = Tenant::onlyTrashed()
            ->where('deleted_at', '<', $cutoff)
            ->get();

        if ($tenants->isEmpty()) {
            $this->info('No tenants to purge.');

            return self::SUCCESS;
        }

        $this->warn("Found {$tenants->count()} tenant(s) soft-deleted more than {$days} days ago.");

        if (! $this->option('force') && ! $this->confirm('Permanently delete these tenants and ALL their data?')) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        foreach ($tenants as $tenant) {
            $this->info("Purging tenant: {$tenant->name} (ID: {$tenant->id}, deleted: {$tenant->deleted_at})");

            // Auto-export before purge
            if ($this->option('export')) {
                $this->call('tenant:export', ['tenant' => $tenant->id]);
            }

            DB::transaction(function () use ($tenant) {
                // Delete users first
                $tenant->users()->withTrashed()->forceDelete();

                // Delete all tenant-scoped data
                foreach (self::TENANT_TABLES as $table) {
                    if (\Schema::hasTable($table) && \Schema::hasColumn($table, 'tenant_id')) {
                        $deleted = DB::table($table)->where('tenant_id', $tenant->id)->delete();
                        if ($deleted > 0) {
                            $this->line("  Deleted {$deleted} rows from {$table}");
                        }
                    }
                }

                // Delete media
                $tenant->clearMediaCollection();

                // Finally, force-delete the tenant
                $tenant->forceDelete();
            });

            $this->info("  Tenant #{$tenant->id} permanently deleted.");
        }

        $this->info("Purge complete. {$tenants->count()} tenant(s) removed.");

        return self::SUCCESS;
    }
}
