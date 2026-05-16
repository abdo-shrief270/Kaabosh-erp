<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repurpose the `subscriptions` table for firm-scoped billing (Model B).
 * The legacy tenant_id column is kept (nullable) so historical rows remain
 * intact; new rows from AuthService::register write firm_id only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->foreignId('firm_id')->nullable()->after('id')->constrained('firms')->cascadeOnDelete();
            $table->index('firm_id');
        });

        // tenant_id must become nullable for firm-scoped rows. Use raw SQL
        // since Doctrine isn't installed and Postgres needs explicit type.
        \DB::statement('ALTER TABLE subscriptions ALTER COLUMN tenant_id DROP NOT NULL');
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropIndex(['firm_id']);
            $table->dropConstrainedForeignId('firm_id');
        });
    }
};
