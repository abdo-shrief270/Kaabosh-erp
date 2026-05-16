<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('accounts')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->index(['tenant_id', 'type', 'is_active'], 'accounts_tenant_type_active_idx');
            });
        }

        if (Schema::hasTable('journal_entries')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->index(['tenant_id', 'status', 'date'], 'je_tenant_status_date_idx');
            });
        }

        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->index('client_id', 'invoices_client_id_idx');
            });
        }

        if (Schema::hasTable('fiscal_years') && ! Schema::hasColumn('fiscal_years', 'deleted_at')) {
            Schema::table('fiscal_years', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('fiscal_periods') && ! Schema::hasColumn('fiscal_periods', 'deleted_at')) {
            Schema::table('fiscal_periods', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('accounts')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->dropIndex('accounts_tenant_type_active_idx');
            });
        }
        if (Schema::hasTable('journal_entries')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->dropIndex('je_tenant_status_date_idx');
            });
        }
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropIndex('invoices_client_id_idx');
            });
        }
        if (Schema::hasColumn('fiscal_years', 'deleted_at')) {
            Schema::table('fiscal_years', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
        if (Schema::hasColumn('fiscal_periods', 'deleted_at')) {
            Schema::table('fiscal_periods', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
