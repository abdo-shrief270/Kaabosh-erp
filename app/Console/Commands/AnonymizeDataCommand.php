<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Anonymizes PII (Personally Identifiable Information) in the database.
 * Used to create safe staging/dev copies from production data.
 *
 * SAFETY: Refuses to run in production unless --force is used.
 * Preserves: IDs, relationships, financial totals, statuses, dates, structure.
 * Anonymizes: Names, emails, phones, addresses, tax IDs, notes, messages.
 */
class AnonymizeDataCommand extends Command
{
    protected $signature = 'data:anonymize
        {--force : Allow running in production (DANGEROUS)}
        {--preserve-admins : Keep super admin accounts unchanged}';

    protected $description = 'Anonymize PII in all tables for staging/dev environments';

    private int $counter = 0;

    public function handle(): int
    {
        if (app()->isProduction() && ! $this->option('force')) {
            $this->error('REFUSED: This command cannot run in production without --force.');
            $this->error('This will PERMANENTLY destroy real user data.');

            return self::FAILURE;
        }

        if (! $this->confirm('This will PERMANENTLY anonymize all personal data. Continue?')) {
            return self::SUCCESS;
        }

        $this->info('Starting data anonymization...');
        $startTime = microtime(true);

        DB::transaction(function () {
            $this->anonymizeUsers();
            $this->anonymizeTenants();
            $this->anonymizeClients();
            $this->anonymizeContacts();
            $this->anonymizeInvoices();
            $this->anonymizeEmployees();
            $this->anonymizeMessages();
            $this->anonymizeActivityLogs();
            $this->anonymizeApiLogs();
        });

        $duration = round(microtime(true) - $startTime, 2);
        $this->info("Anonymization complete in {$duration}s. {$this->counter} records modified.");

        return self::SUCCESS;
    }

    private function anonymizeUsers(): void
    {
        $query = DB::table('users');

        if ($this->option('preserve-admins')) {
            $query->where('role', '!=', 'super_admin');
        }

        $count = $query->count();
        $this->info("  Anonymizing {$count} users...");

        $query->orderBy('id')->chunk(500, function ($users) {
            foreach ($users as $user) {
                $i = $this->counter++;
                DB::table('users')->where('id', $user->id)->update([
                    'name' => "مستخدم تجريبي {$i}",
                    'email' => "user{$i}@staging.muhasebi.test",
                    'phone' => '+2010'.str_pad((string) ($i % 100000000), 8, '0', STR_PAD_LEFT),
                    'password' => Hash::make('staging123'),
                    'two_factor_secret' => null,
                    'two_factor_recovery_codes' => null,
                    'two_factor_enabled' => false,
                ]);
            }
        });
    }

    private function anonymizeTenants(): void
    {
        $count = DB::table('tenants')->count();
        $this->info("  Anonymizing {$count} tenants...");

        DB::table('tenants')->orderBy('id')->chunk(200, function ($tenants) {
            foreach ($tenants as $tenant) {
                $i = $this->counter++;
                DB::table('tenants')->where('id', $tenant->id)->update([
                    'name' => "شركة تجريبية {$i}",
                    'email' => "tenant{$i}@staging.muhasebi.test",
                    'phone' => '+2012'.str_pad((string) ($i % 10000000), 7, '0', STR_PAD_LEFT),
                    'tax_id' => $tenant->tax_id ? ('TAX'.str_pad((string) $i, 9, '0', STR_PAD_LEFT)) : null,
                    'commercial_register' => $tenant->commercial_register ? ('CR'.str_pad((string) $i, 8, '0', STR_PAD_LEFT)) : null,
                    'address' => $tenant->address ? "عنوان تجريبي {$i}" : null,
                ]);
            }
        });
    }

    private function anonymizeClients(): void
    {
        $count = DB::table('clients')->count();
        $this->info("  Anonymizing {$count} clients...");

        DB::table('clients')->orderBy('id')->chunk(500, function ($clients) {
            foreach ($clients as $client) {
                $i = $this->counter++;
                DB::table('clients')->where('id', $client->id)->update([
                    'name' => "عميل {$i}",
                    'trade_name' => $client->trade_name ? "اسم تجاري {$i}" : null,
                    'email' => $client->email ? "client{$i}@staging.test" : null,
                    'phone' => $client->phone ? ('+2011'.str_pad((string) ($i % 10000000), 7, '0', STR_PAD_LEFT)) : null,
                    'tax_id' => $client->tax_id ? ('CT'.str_pad((string) $i, 9, '0', STR_PAD_LEFT)) : null,
                    'address' => $client->address ? "شارع {$i}، القاهرة" : null,
                ]);
            }
        });
    }

    private function anonymizeContacts(): void
    {
        if (! \Schema::hasTable('contact_submissions')) {
            return;
        }

        $count = DB::table('contact_submissions')->count();
        $this->info("  Anonymizing {$count} contact submissions...");

        DB::table('contact_submissions')->orderBy('id')->chunk(500, function ($contacts) {
            foreach ($contacts as $contact) {
                $i = $this->counter++;
                DB::table('contact_submissions')->where('id', $contact->id)->update([
                    'name' => "جهة اتصال {$i}",
                    'email' => "contact{$i}@staging.test",
                    'phone' => null,
                    'company' => $contact->company ? "شركة {$i}" : null,
                    'message' => 'رسالة تجريبية للاختبار.',
                    'admin_notes' => null,
                ]);
            }
        });
    }

    private function anonymizeInvoices(): void
    {
        // Anonymize notes only (preserve financial data)
        $count = DB::table('invoices')->whereNotNull('notes')->count();
        $this->info("  Anonymizing {$count} invoice notes...");

        DB::table('invoices')->whereNotNull('notes')->update(['notes' => 'ملاحظة تجريبية.']);
        DB::table('invoices')->whereNotNull('terms')->update(['terms' => 'شروط دفع تجريبية.']);
    }

    private function anonymizeEmployees(): void
    {
        if (! \Schema::hasTable('employees')) {
            return;
        }

        $count = DB::table('employees')->count();
        $this->info("  Anonymizing {$count} employees...");

        DB::table('employees')->orderBy('id')->chunk(500, function ($employees) {
            foreach ($employees as $emp) {
                $i = $this->counter++;
                $updates = ['name' => "موظف {$i}"];

                if (\Schema::hasColumn('employees', 'email')) {
                    $updates['email'] = "emp{$i}@staging.test";
                }
                if (\Schema::hasColumn('employees', 'phone')) {
                    $updates['phone'] = '+2015'.str_pad((string) ($i % 10000000), 7, '0', STR_PAD_LEFT);
                }
                if (\Schema::hasColumn('employees', 'national_id')) {
                    $updates['national_id'] = str_pad((string) $i, 14, '0', STR_PAD_LEFT);
                }
                if (\Schema::hasColumn('employees', 'bank_account')) {
                    $updates['bank_account'] = null;
                }

                DB::table('employees')->where('id', $emp->id)->update($updates);
            }
        });
    }

    private function anonymizeMessages(): void
    {
        if (! \Schema::hasTable('messages')) {
            return;
        }

        $count = DB::table('messages')->count();
        $this->info("  Anonymizing {$count} messages...");

        DB::table('messages')->update(['body' => 'رسالة تجريبية.']);
    }

    private function anonymizeActivityLogs(): void
    {
        if (! \Schema::hasTable('activity_log')) {
            return;
        }

        // Remove activity log properties that may contain PII
        $count = DB::table('activity_log')->count();
        $this->info("  Clearing {$count} activity log properties...");

        DB::table('activity_log')->update(['properties' => '{}']);
    }

    private function anonymizeApiLogs(): void
    {
        if (! \Schema::hasTable('api_request_logs')) {
            return;
        }

        $count = DB::table('api_request_logs')->count();
        $this->info("  Anonymizing {$count} API logs...");

        DB::table('api_request_logs')->update([
            'ip' => '127.0.0.1',
            'user_agent' => 'Staging/1.0',
            'request_headers' => null,
            'request_body' => null,
            'error_message' => null,
        ]);
    }
}
