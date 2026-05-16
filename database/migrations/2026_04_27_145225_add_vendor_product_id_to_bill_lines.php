<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds `vendor_product_id` to `bill_lines`, mirroring `invoice_lines.client_product_id`.
 *
 * Tracking which saved vendor product a line was sourced from lets the
 * BillLine observer bump `vendor_products.last_used_at` on insert — which
 * in turn lets the bill-create line picker surface frequent items first
 * and the catalog page's "Last used" column show real data.
 *
 * `nullOnDelete` because losing the link silently is preferable to
 * cascading away the bill line when the product is deleted; the snapshot
 * fields (description, unit_price, vat_rate) keep the line whole.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bill_lines', function (Blueprint $table): void {
            $table->foreignId('vendor_product_id')
                ->nullable()
                ->after('account_id')
                ->constrained('vendor_products')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bill_lines', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('vendor_product_id');
        });
    }
};
