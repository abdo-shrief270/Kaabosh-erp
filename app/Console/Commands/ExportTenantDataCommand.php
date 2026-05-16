<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Tenant\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * GDPR-compliant full data export for a tenant.
 * Exports all tenant-owned data into a structured JSON archive.
 */
class ExportTenantDataCommand extends Command
{
    protected $signature = 'tenant:export {tenant : Tenant ID or slug} {--disk=local : Storage disk}';

    protected $description = 'Export all data for a tenant (GDPR data portability)';

    /** Tables that hold tenant-scoped data. */
    private const TENANT_TABLES = [
        'clients',
        'accounts',
        'fiscal_years',
        'fiscal_periods',
        'journal_entries',
        'journal_entry_lines',
        'invoices',
        'invoice_lines',
        'payments',
        'documents',
        'eta_settings',
        'eta_submissions',
        'eta_documents',
        'eta_item_codes',
        'timesheet_entries',
        'timers',
        'employees',
        'payroll_runs',
        'payroll_items',
        'subscriptions',
        'messages',
        'notifications',
        'onboarding_steps',
        'storage_quotas',
        'usage_records',
        'invoice_settings',
    ];

    public function handle(): int
    {
        $identifier = $this->argument('tenant');

        $tenant = is_numeric($identifier)
            ? Tenant::withTrashed()->find($identifier)
            : Tenant::withTrashed()->where('slug', $identifier)->first();

        if (! $tenant) {
            $this->error("Tenant not found: {$identifier}");

            return self::FAILURE;
        }

        $this->info("Exporting data for tenant: {$tenant->name} (ID: {$tenant->id})...");

        $export = [
            'export_meta' => [
                'version' => '1.0',
                'exported_at' => now()->toISOString(),
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'tenant_slug' => $tenant->slug,
                'format' => 'GDPR Data Portability Export',
            ],
            'tenant' => $tenant->toArray(),
            'users' => $tenant->users()->withTrashed()->get()->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'role' => $u->role?->value ?? $u->role,
                'locale' => $u->locale,
                'timezone' => $u->timezone,
                'is_active' => $u->is_active,
                'created_at' => $u->created_at?->toISOString(),
                'last_login_at' => $u->last_login_at?->toISOString(),
            ])->toArray(),
        ];

        // Large tables that should use chunked processing to avoid memory exhaustion
        $chunkedTables = ['invoices', 'journal_entries', 'journal_entry_lines', 'payments'];

        // Export each tenant table
        foreach (self::TENANT_TABLES as $table) {
            if (! \Schema::hasTable($table)) {
                continue;
            }

            if (! \Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            $this->info("  Exporting {$table}...");

            if (in_array($table, $chunkedTables, true)) {
                $records = [];
                DB::table($table)
                    ->where('tenant_id', $tenant->id)
                    ->orderBy('id')
                    ->chunk(500, function ($chunk) use (&$records) {
                        foreach ($chunk as $row) {
                            $records[] = (array) $row;
                        }
                    });
                $data = $records;
            } else {
                $data = DB::table($table)
                    ->where('tenant_id', $tenant->id)
                    ->get()
                    ->map(fn ($row) => (array) $row)
                    ->toArray();
            }

            $export[$table] = [
                'count' => count($data),
                'records' => $data,
            ];
        }

        // Save to disk
        $filename = sprintf('exports/tenant_%d_%s.json', $tenant->id, now()->format('Y-m-d_His'));
        $disk = $this->option('disk');

        Storage::disk($disk)->put($filename, json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $path = Storage::disk($disk)->path($filename);
        $sizeMb = round(filesize($path) / 1024 / 1024, 2);

        $this->info("Export complete: {$filename} ({$sizeMb} MB)");
        $this->info('Tables exported: '.count(array_filter($export, fn ($v) => is_array($v) && isset($v['count']))));

        return self::SUCCESS;
    }
}
