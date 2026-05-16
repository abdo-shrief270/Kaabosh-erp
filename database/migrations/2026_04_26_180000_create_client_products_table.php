<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * client_products — per-client billable items (services, retainers, custom
 * priced items). Distinct from inventory.products which are tenant-wide
 * stocked goods. A client_product belongs to exactly one client and exists
 * to (a) speed up invoice creation by pre-filling description / unit price /
 * VAT, and (b) give the firm a per-client price book.
 *
 * No stock tracking, no SKU, no GL accounts here — those concerns belong to
 * inventory.products. If a tenant later wants to bill a stocked good, they
 * just pick it manually on the invoice line; we don't dual-write.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();
            $table->foreignId('client_id')
                ->constrained('clients')
                ->cascadeOnDelete();

            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->decimal('unit_price', 14, 2);
            // null = inherit tenant default VAT rate at invoice time.
            $table->decimal('default_vat_rate', 5, 2)->nullable();
            $table->boolean('is_active')->default(true);

            // Recency hint for the picker — last time this item appeared on
            // an invoice line for its client. Updated by a small observer on
            // invoice line insert; defaults to null.
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Most queries are: list by client, filter active, recent-first.
            $table->index(['tenant_id', 'client_id', 'is_active']);
            $table->index(['tenant_id', 'last_used_at']);
            // Tenant + client + name — guards against accidental duplicates;
            // app code will surface as a 422 with a friendly message.
            $table->unique(['tenant_id', 'client_id', 'name'], 'client_products_unique_name_per_client');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_products');
    }
};
