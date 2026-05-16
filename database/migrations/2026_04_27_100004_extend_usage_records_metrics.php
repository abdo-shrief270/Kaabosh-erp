<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usage_records', function (Blueprint $table): void {
            $table->unsignedInteger('bills_count')->default(0)->after('invoices_count');
            $table->unsignedInteger('journal_entries_count')->default(0)->after('bills_count');
            $table->unsignedInteger('bank_imports_count')->default(0)->after('journal_entries_count');
            $table->unsignedInteger('documents_count')->default(0)->after('bank_imports_count');
            $table->unsignedInteger('api_calls_count')->default(0)->after('documents_count');
        });
    }

    public function down(): void
    {
        Schema::table('usage_records', function (Blueprint $table): void {
            $table->dropColumn([
                'bills_count',
                'journal_entries_count',
                'bank_imports_count',
                'documents_count',
                'api_calls_count',
            ]);
        });
    }
};
