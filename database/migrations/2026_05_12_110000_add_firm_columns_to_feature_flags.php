<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SuperAdmin escape hatch for firm-level feature overrides.
 *
 * The default resolution is: firm.subscription_tier → manifest. These
 * columns override that — `enabled_for_firms[]` force ON, `disabled_for_firms[]`
 * force OFF, regardless of plan tier.
 *
 * Mirrors the existing `enabled_for_tenants`/`disabled_for_tenants` shape
 * so the Filament UI and observer logic stay consistent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feature_flags', function (Blueprint $table): void {
            $table->json('enabled_for_firms')->nullable()->after('disabled_for_tenants');
            $table->json('disabled_for_firms')->nullable()->after('enabled_for_firms');
        });
    }

    public function down(): void
    {
        Schema::table('feature_flags', function (Blueprint $table): void {
            $table->dropColumn(['enabled_for_firms', 'disabled_for_firms']);
        });
    }
};
