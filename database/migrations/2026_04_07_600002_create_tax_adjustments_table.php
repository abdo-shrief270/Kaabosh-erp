<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years')->cascadeOnDelete();

            $table->string('adjustment_type', 20)->comment('نوع التسوية الضريبية');
            $table->string('description_ar')->comment('وصف التسوية بالعربية');
            $table->string('description_en')->nullable()->comment('وصف التسوية بالإنجليزية');
            $table->decimal('amount', 15, 2);
            $table->boolean('is_addition')->comment('إضافة أو خصم من الوعاء الضريبي');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'fiscal_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_adjustments');
    }
};
