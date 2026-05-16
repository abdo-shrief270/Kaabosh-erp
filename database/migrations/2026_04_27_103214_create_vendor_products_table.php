<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * vendor_products — per-vendor billable items used to pre-fill bill lines.
 * Mirror of client_products on the AR side. Distinct from inventory.products
 * (stocked goods) for the same reasons explained in that migration.
 *
 * Carries `default_account_id`: when the user picks a saved vendor product
 * on a bill line, the picker fills not just description / unit price / VAT
 * but also the GL account — most vendors post repeatedly to the same
 * expense account, so caching the choice removes the most common source
 * of slow data-entry on bill creation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();
            $table->foreignId('vendor_id')
                ->constrained('vendors')
                ->cascadeOnDelete();

            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->decimal('unit_price', 14, 2);
            // null = inherit tenant default VAT rate at bill time.
            $table->decimal('default_vat_rate', 5, 2)->nullable();
            // null = no preferred GL account; user must pick at line time.
            // restrictOnDelete because a bill line might still reference
            // this product's prior selection — losing the account silently
            // would corrupt the user's expectations.
            $table->foreignId('default_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();
            $table->boolean('is_active')->default(true);

            // Recency hint for the picker — last time this item appeared on
            // a bill line for its vendor. Updated by an observer on bill
            // line insert; defaults to null.
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Most queries are: list by vendor, filter active, recent-first.
            $table->index(['tenant_id', 'vendor_id', 'is_active']);
            $table->index(['tenant_id', 'last_used_at']);

            // Prevents duplicate item names per vendor (per tenant). Catches
            // the common copy-paste mistake of saving the same product twice.
            $table->unique(['tenant_id', 'vendor_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_products');
    }
};
