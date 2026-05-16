<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalog of add-ons that tenants can purchase on top of their plan.
 *
 * Three flavors:
 *  - boost        — recurring, raises one or more plan limits
 *                   (e.g. +5 users, +5 GB storage). The `boost` JSON map
 *                   uses the same keys as Plan::limits.
 *  - feature      — recurring, unlocks a feature (e.g. white_label).
 *                   The `feature_slug` is matched against the same
 *                   feature flag registry the plan uses.
 *  - credit_pack  — one-time purchase of N credits of a given kind
 *                   (e.g. 1 000 SMS, 100 000 AI tokens). Drains over
 *                   time via add_on_credits.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('add_ons', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name_en', 100);
            $table->string('name_ar', 100);
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();

            // boost | feature | credit_pack
            $table->string('type', 20)->index();

            // monthly | annual | once
            $table->string('billing_cycle', 10)->default('monthly');

            // For boost type: { "users": 5, "storage_bytes": 5368709120 }
            $table->json('boost')->nullable();

            // For feature type
            $table->string('feature_slug', 64)->nullable();

            // For credit_pack type
            $table->string('credit_kind', 50)->nullable()->index();
            $table->unsignedBigInteger('credit_quantity')->nullable();

            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_annual', 10, 2)->default(0);
            $table->decimal('price_once', 10, 2)->default(0);
            $table->string('currency', 3)->default('EGP');

            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('add_ons');
    }
};
