<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per-firm add-on rows. The `add_ons` catalog stays as-is — same catalog
 * available to every firm — but the activations move from tenant-scope to
 * firm-scope. Legacy tenant_id stays nullable so historical rows survive.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_add_ons', function (Blueprint $table): void {
            $table->foreignId('firm_id')->nullable()->after('id')->constrained('firms')->cascadeOnDelete();
            $table->index('firm_id');
        });

        // Per-firm add-ons no longer need a tenant. Postgres needs raw SQL.
        DB::statement('ALTER TABLE subscription_add_ons ALTER COLUMN tenant_id DROP NOT NULL');
    }

    public function down(): void
    {
        Schema::table('subscription_add_ons', function (Blueprint $table): void {
            $table->dropIndex(['firm_id']);
            $table->dropConstrainedForeignId('firm_id');
        });
    }
};
