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
        $tables = [
            'bank_statement_lines' => 'bank_reconciliations',
            'budget_lines' => 'budgets',
            'journal_entry_lines' => 'journal_entries',
            'invoice_lines' => 'invoices',
        ];

        foreach ($tables as $child => $parent) {
            Schema::table($child, function (Blueprint $table): void {
                $table->foreignId('tenant_id')
                    ->after('id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->index('tenant_id');
            });

            // Back-fill tenant_id from the parent table
            $parentFk = match ($child) {
                'bank_statement_lines' => 'reconciliation_id',
                'budget_lines' => 'budget_id',
                'journal_entry_lines' => 'journal_entry_id',
                'invoice_lines' => 'invoice_id',
            };

            DB::statement("
                UPDATE {$child}
                SET tenant_id = (
                    SELECT {$parent}.tenant_id
                    FROM {$parent}
                    WHERE {$parent}.id = {$child}.{$parentFk}
                )
            ");
        }
    }

    public function down(): void
    {
        foreach (['bank_statement_lines', 'budget_lines', 'journal_entry_lines', 'invoice_lines'] as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropConstrainedForeignId('tenant_id');
            });
        }
    }
};
