<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Credit wallet rows created when a credit_pack add-on is purchased.
 *
 * One row per purchase. Aggregating remaining balance per (tenant, kind)
 * happens at read time via SUM(quantity_total - quantity_used) where
 * expires_at IS NULL OR expires_at > NOW(). FIFO consumption: the oldest
 * non-expired pack drains first.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('add_on_credits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('subscription_add_on_id')->constrained('subscription_add_ons')->cascadeOnDelete();
            $table->string('kind', 50);
            $table->unsignedBigInteger('quantity_total');
            $table->unsignedBigInteger('quantity_used')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'kind']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('add_on_credits');
    }
};
