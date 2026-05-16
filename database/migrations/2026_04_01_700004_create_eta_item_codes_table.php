<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eta_item_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('code_type', 10);
            $table->string('item_code', 100);
            $table->string('description', 500);
            $table->string('description_ar', 500)->nullable();
            $table->string('unit_type', 10)->default('EA');
            $table->string('default_tax_type', 10)->default('T1');
            $table->string('default_tax_subtype', 10)->default('V009');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'item_code']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eta_item_codes');
    }
};
