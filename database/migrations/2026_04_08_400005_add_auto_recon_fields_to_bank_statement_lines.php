<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_statement_lines', function (Blueprint $table): void {
            $table->boolean('auto_matched')->default(false);
            $table->decimal('match_confidence', 5, 2)->nullable();
            $table->foreignId('matched_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('matched_bill_id')->nullable()->constrained('bills')->nullOnDelete();
            $table->boolean('auto_posted')->default(false);
            $table->foreignId('posted_journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bank_statement_lines', function (Blueprint $table): void {
            $table->dropColumn('auto_matched');
            $table->dropColumn('match_confidence');
            $table->dropConstrainedForeignId('matched_invoice_id');
            $table->dropConstrainedForeignId('matched_bill_id');
            $table->dropColumn('auto_posted');
            $table->dropConstrainedForeignId('posted_journal_entry_id');
        });
    }
};
