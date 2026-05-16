<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eta_item_code_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_sku', 50)->nullable();
            $table->string('description_pattern', 255)->comment('Keyword or regex to match invoice line descriptions');
            $table->foreignId('eta_item_code_id')->constrained('eta_item_codes')->cascadeOnDelete();
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'product_id']);
            $table->index(['tenant_id', 'is_active', 'priority']);
        });

        // Partial unique index: one mapping per product per tenant (only when product_id is set)
        DB::statement('CREATE UNIQUE INDEX eta_item_code_mappings_tenant_product_unique ON eta_item_code_mappings (tenant_id, product_id) WHERE product_id IS NOT NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('eta_item_code_mappings');
    }
};
