<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fx_revaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->date('revaluation_date');
            $table->string('functional_currency', 3)->default('EGP');
            $table->string('status', 20)->default('draft'); // draft, posted
            $table->decimal('total_gain', 15, 2)->default(0);
            $table->decimal('total_loss', 15, 2)->default(0);
            $table->decimal('net_gain_loss', 15, 2)->default(0);
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'revaluation_date']);
        });

        Schema::create('fx_revaluation_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('revaluation_id')->constrained('fx_revaluations')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts');
            $table->string('currency', 3);
            $table->decimal('original_balance', 15, 2);
            $table->decimal('original_rate', 12, 6);
            $table->decimal('new_rate', 12, 6);
            $table->decimal('revalued_balance', 15, 2);
            $table->decimal('gain_loss', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fx_revaluation_lines');
        Schema::dropIfExists('fx_revaluations');
    }
};
