<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eta_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained('tenants')->cascadeOnDelete();
            $table->boolean('is_enabled')->default(false);
            $table->string('environment', 20)->default('preprod');
            $table->string('client_id', 255)->nullable();
            $table->text('client_secret')->nullable();
            $table->string('branch_id', 20)->default('0');
            $table->string('branch_address_country', 5)->default('EG');
            $table->string('branch_address_governate', 100)->nullable();
            $table->string('branch_address_region_city', 100)->nullable();
            $table->string('branch_address_street', 200)->nullable();
            $table->string('branch_address_building_number', 50)->nullable();
            $table->string('activity_code', 10)->nullable();
            $table->string('company_trade_name', 255)->nullable();
            $table->text('access_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eta_settings');
    }
};
