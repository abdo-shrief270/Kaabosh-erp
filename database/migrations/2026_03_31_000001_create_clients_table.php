<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            // Identity
            $table->string('name');
            $table->string('trade_name')->nullable()->comment('الاسم التجاري');
            $table->string('tax_id', 20)->nullable()->comment('الرقم الضريبي');
            $table->string('commercial_register', 30)->nullable()->comment('السجل التجاري');
            $table->string('activity_type', 100)->nullable()->comment('نوع النشاط');

            // Address
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();

            // Contact
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('contact_person')->nullable()->comment('المسؤول');
            $table->string('contact_phone', 20)->nullable();

            // Meta
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('tenant_id');
            $table->index('is_active');
            $table->index('tax_id');
            $table->index('commercial_register');
            $table->unique(['tenant_id', 'tax_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
