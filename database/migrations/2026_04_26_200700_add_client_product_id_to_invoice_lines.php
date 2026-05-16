<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link invoice_lines to client_products. Nullable on purpose: a line can be
 * sourced from a saved per-client product (FK populated, snapshot fields
 * still copied so renaming the product doesn't rewrite history) OR be
 * freeform (FK null, line stands on its own).
 *
 * `set null` on delete: archiving a product shouldn't break invoice history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_lines', function (Blueprint $table): void {
            $table->foreignId('client_product_id')
                ->nullable()
                ->after('invoice_id')
                ->constrained('client_products')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoice_lines', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('client_product_id');
        });
    }
};
