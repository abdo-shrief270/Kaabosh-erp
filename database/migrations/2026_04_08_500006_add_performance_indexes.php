<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Regular composite indexes for query performance ──

        Schema::table('activity_log', function (Blueprint $table): void {
            $table->index(['subject_type', 'subject_id'], 'idx_activity_log_subject');
            $table->index(['created_at'], 'idx_activity_log_created_at');
        });

        Schema::table('journal_entry_lines', function (Blueprint $table): void {
            $table->index(['account_id', 'journal_entry_id'], 'idx_jel_account_journal');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->index(['invoice_id', 'created_at'], 'idx_payments_invoice_created');
        });

        Schema::table('fiscal_periods', function (Blueprint $table): void {
            $table->index(['start_date', 'end_date'], 'idx_fiscal_periods_date_range');
        });

        Schema::table('payroll_runs', function (Blueprint $table): void {
            $table->index(['tenant_id', 'status', 'created_at'], 'idx_payroll_runs_tenant_status_created');
        });

        Schema::table('timesheet_entries', function (Blueprint $table): void {
            $table->index(['tenant_id', 'status', 'date'], 'idx_timesheet_entries_tenant_status_date');
        });

        Schema::table('timers', function (Blueprint $table): void {
            $table->index(['user_id', 'is_running'], 'idx_timers_user_running');
        });

        // ── pg_trgm GIN indexes for ILIKE search performance ──

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        // accounts
        DB::statement('CREATE INDEX IF NOT EXISTS idx_accounts_name_ar_trgm ON accounts USING GIN (name_ar gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_accounts_name_en_trgm ON accounts USING GIN (name_en gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_accounts_code_trgm ON accounts USING GIN (code gin_trgm_ops)');

        // clients
        DB::statement('CREATE INDEX IF NOT EXISTS idx_clients_name_trgm ON clients USING GIN (name gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_clients_email_trgm ON clients USING GIN (email gin_trgm_ops)');

        // invoices
        DB::statement('CREATE INDEX IF NOT EXISTS idx_invoices_invoice_number_trgm ON invoices USING GIN (invoice_number gin_trgm_ops)');

        // bills
        DB::statement('CREATE INDEX IF NOT EXISTS idx_bills_bill_number_trgm ON bills USING GIN (bill_number gin_trgm_ops)');

        // expenses
        DB::statement('CREATE INDEX IF NOT EXISTS idx_expenses_description_trgm ON expenses USING GIN (description gin_trgm_ops)');

        // fixed_assets
        DB::statement('CREATE INDEX IF NOT EXISTS idx_fixed_assets_name_ar_trgm ON fixed_assets USING GIN (name_ar gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_fixed_assets_name_en_trgm ON fixed_assets USING GIN (name_en gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_fixed_assets_code_trgm ON fixed_assets USING GIN (code gin_trgm_ops)');

        // vendors — schema uses name_ar/name_en (no single 'name' column)
        DB::statement('CREATE INDEX IF NOT EXISTS idx_vendors_name_ar_trgm ON vendors USING GIN (name_ar gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_vendors_name_en_trgm ON vendors USING GIN (name_en gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_vendors_email_trgm ON vendors USING GIN (email gin_trgm_ops)');
    }

    public function down(): void
    {
        // ── Drop regular composite indexes ──

        Schema::table('activity_log', function (Blueprint $table): void {
            $table->dropIndex('idx_activity_log_subject');
            $table->dropIndex('idx_activity_log_created_at');
        });

        Schema::table('journal_entry_lines', function (Blueprint $table): void {
            $table->dropIndex('idx_jel_account_journal');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex('idx_payments_invoice_created');
        });

        Schema::table('fiscal_periods', function (Blueprint $table): void {
            $table->dropIndex('idx_fiscal_periods_date_range');
        });

        Schema::table('payroll_runs', function (Blueprint $table): void {
            $table->dropIndex('idx_payroll_runs_tenant_status_created');
        });

        Schema::table('timesheet_entries', function (Blueprint $table): void {
            $table->dropIndex('idx_timesheet_entries_tenant_status_date');
        });

        Schema::table('timers', function (Blueprint $table): void {
            $table->dropIndex('idx_timers_user_running');
        });

        // ── Drop pg_trgm GIN indexes ──

        DB::statement('DROP INDEX IF EXISTS idx_accounts_name_ar_trgm');
        DB::statement('DROP INDEX IF EXISTS idx_accounts_name_en_trgm');
        DB::statement('DROP INDEX IF EXISTS idx_accounts_code_trgm');

        DB::statement('DROP INDEX IF EXISTS idx_clients_name_trgm');
        DB::statement('DROP INDEX IF EXISTS idx_clients_email_trgm');

        DB::statement('DROP INDEX IF EXISTS idx_invoices_invoice_number_trgm');

        DB::statement('DROP INDEX IF EXISTS idx_bills_bill_number_trgm');

        DB::statement('DROP INDEX IF EXISTS idx_expenses_description_trgm');

        DB::statement('DROP INDEX IF EXISTS idx_fixed_assets_name_ar_trgm');
        DB::statement('DROP INDEX IF EXISTS idx_fixed_assets_name_en_trgm');
        DB::statement('DROP INDEX IF EXISTS idx_fixed_assets_code_trgm');

        DB::statement('DROP INDEX IF EXISTS idx_vendors_name_ar_trgm');
        DB::statement('DROP INDEX IF EXISTS idx_vendors_name_en_trgm');
        DB::statement('DROP INDEX IF EXISTS idx_vendors_email_trgm');
    }
};
