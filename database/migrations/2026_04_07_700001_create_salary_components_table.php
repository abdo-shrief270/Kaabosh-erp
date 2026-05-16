<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('code', 20);
            $table->string('type', 20); // allowance, deduction, contribution
            $table->string('calculation_type', 20); // fixed, percentage_of_basic, percentage_of_gross
            $table->decimal('default_amount', 15, 2)->default(0);
            $table->decimal('default_percentage', 5, 2)->default(0);
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_social_insurable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_components');
    }
};
