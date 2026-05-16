<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('name_en', 100);
            $table->string('name_ar', 100);
            $table->string('slug', 50)->unique();
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_annual', 10, 2)->default(0);
            $table->string('currency', 3)->default('EGP');
            $table->unsignedSmallInteger('trial_days')->default(14);
            $table->json('limits')->comment('حدود الاستخدام: max_users, max_clients, max_storage_bytes, max_invoices_per_month');
            $table->json('features')->comment('الميزات: e_invoice, api_access, custom_reports, client_portal, priority_support');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('slug');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
