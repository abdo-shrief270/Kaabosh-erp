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
        Schema::create('journal_entry_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts');

            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->string('description', 500)->nullable()->comment('ملاحظة على مستوى السطر');
            $table->string('cost_center', 50)->nullable()->comment('مركز التكلفة');

            $table->timestamps();

            // Indexes
            $table->index('journal_entry_id');
            $table->index('account_id');
        });

        // CHECK constraints for PostgreSQL
        DB::statement('ALTER TABLE journal_entry_lines ADD CONSTRAINT chk_amounts_non_negative CHECK (debit >= 0 AND credit >= 0)');
        DB::statement('ALTER TABLE journal_entry_lines ADD CONSTRAINT chk_single_side CHECK (NOT (debit > 0 AND credit > 0))');
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
    }
};
