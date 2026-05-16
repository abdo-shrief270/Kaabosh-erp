<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('code', 20)->nullable();
            $table->string('tax_id', 20)->nullable();
            $table->string('commercial_register', 30)->nullable();
            $table->string('vat_registration', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('address_ar')->nullable();
            $table->text('address_en')->nullable();
            $table->string('city')->nullable();
            $table->string('country', 2)->default('EG');
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('iban', 34)->nullable();
            $table->string('swift_code', 11)->nullable();
            $table->string('payment_terms', 20)->default('net_30');
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->string('currency', 3)->default('EGP');
            $table->json('contacts')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'name_ar']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
