<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_returns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years')->cascadeOnDelete();

            $table->string('return_type', 20)->comment('نوع الإقرار الضريبي');
            $table->date('period_from');
            $table->date('period_to');
            $table->string('status', 20)->default('draft');
            $table->decimal('gross_revenue', 15, 2)->default(0);
            $table->decimal('total_expenses', 15, 2)->default(0);
            $table->decimal('adjustments_total', 15, 2)->default(0);
            $table->decimal('taxable_income', 15, 2)->default(0);
            $table->decimal('tax_due', 15, 2)->default(0);
            $table->decimal('tax_paid', 15, 2)->default(0);
            $table->decimal('balance_due', 15, 2)->default(0);
            $table->timestamp('filed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('filing_reference')->nullable();
            $table->text('notes')->nullable();
            $table->json('data')->nullable()->comment('تفاصيل الإقرار');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'return_type', 'status']);
            $table->index(['tenant_id', 'fiscal_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_returns');
    }
};
