<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds `default_account_id` to `client_products`, mirroring the column on
 * `vendor_products`. When the user picks a saved client product on an
 * invoice line the editor fills not just description / unit price / VAT
 * but also the GL account — most clients invoice repeatedly against the
 * same revenue account, so caching the choice removes the most common
 * source of slow data-entry on invoice creation.
 *
 * `nullOnDelete` because losing the account silently is preferable to
 * cascading away the entire saved product when an account is deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_products', function (Blueprint $table): void {
            $table->foreignId('default_account_id')
                ->nullable()
                ->after('default_vat_rate')
                ->constrained('accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('client_products', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('default_account_id');
        });
    }
};
