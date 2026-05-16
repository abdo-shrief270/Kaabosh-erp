<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('EGP');
            $table->string('status', 20)->default('pending');
            $table->string('gateway', 20);
            $table->string('gateway_transaction_id', 255)->nullable();
            $table->string('gateway_order_id', 255)->nullable();
            $table->string('payment_method_type', 50)->nullable();
            $table->date('billing_period_start')->nullable();
            $table->date('billing_period_end')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->string('receipt_url', 500)->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('tenant_id');
            $table->index('subscription_id');
            $table->index('status');
            $table->index('gateway_transaction_id');
            $table->index('gateway_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
