<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A subscription's purchased instance of an add-on.
 *
 * Stacking is handled by `quantity` — three "+5 users" packs purchased on
 * one subscription = a single row with quantity=3, contributing +15 users
 * to the effective limit. This keeps the active-add-ons list short for
 * the UI while still allowing tenants to buy multiples.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_add_ons', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->foreignId('add_on_id')->constrained('add_ons')->restrictOnDelete();

            $table->unsignedInteger('quantity')->default(1);

            // active | cancelled | expired
            $table->string('status', 20)->default('active');

            // monthly | annual | once
            $table->string('billing_cycle', 10)->default('monthly');

            // Per-unit price locked in at purchase time.
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default('EGP');

            $table->date('current_period_start')->nullable();
            $table->date('current_period_end')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->timestamp('expires_at')->nullable();

            $table->string('gateway', 20)->nullable();
            $table->string('gateway_payment_id', 255)->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('tenant_id');
            $table->index('subscription_id');
            $table->index(['subscription_id', 'status']);
            $table->index('add_on_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_add_ons');
    }
};
