<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained('tenants')->cascadeOnDelete();

            $table->string('invoice_prefix', 10)->default('INV');
            $table->string('credit_note_prefix', 10)->default('CN');
            $table->string('debit_note_prefix', 10)->default('DN');
            $table->unsignedInteger('next_invoice_number')->default(1);
            $table->unsignedInteger('next_credit_note_number')->default(1);
            $table->unsignedInteger('next_debit_note_number')->default(1);
            $table->unsignedSmallInteger('default_due_days')->default(30);
            $table->decimal('default_vat_rate', 5, 2)->default(14.00);
            $table->text('default_payment_terms')->nullable();
            $table->text('default_notes')->nullable();
            $table->foreignId('ar_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('revenue_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('vat_account_id')->nullable()->constrained('accounts')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_settings');
    }
};
