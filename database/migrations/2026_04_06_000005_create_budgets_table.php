<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years');
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('status', 20)->default('draft'); // draft, approved, closed
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'fiscal_year_id', 'name']);
        });

        Schema::create('budget_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('budget_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts');
            $table->decimal('annual_amount', 15, 2)->default(0);
            $table->decimal('m1', 15, 2)->default(0);
            $table->decimal('m2', 15, 2)->default(0);
            $table->decimal('m3', 15, 2)->default(0);
            $table->decimal('m4', 15, 2)->default(0);
            $table->decimal('m5', 15, 2)->default(0);
            $table->decimal('m6', 15, 2)->default(0);
            $table->decimal('m7', 15, 2)->default(0);
            $table->decimal('m8', 15, 2)->default(0);
            $table->decimal('m9', 15, 2)->default(0);
            $table->decimal('m10', 15, 2)->default(0);
            $table->decimal('m11', 15, 2)->default(0);
            $table->decimal('m12', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['budget_id', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_lines');
        Schema::dropIfExists('budgets');
    }
};
