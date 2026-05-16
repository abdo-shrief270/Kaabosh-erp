<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique(); // ISO 4217: EGP, USD, EUR, SAR
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('symbol', 10); // ج.م, $, €, ﷼
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('base_currency', 3)->default('EGP');
            $table->string('target_currency', 3);
            $table->decimal('rate', 15, 6); // 1 base = ? target
            $table->date('effective_date');
            $table->string('source')->default('manual'); // manual, api
            $table->timestamps();

            $table->index(['base_currency', 'target_currency', 'effective_date']);
            $table->unique(['base_currency', 'target_currency', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('currencies');
    }
};
