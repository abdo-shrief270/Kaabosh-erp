<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drops the per-tenant landing-page columns now that the feature has been
 * retired. The /company/{slug} route, LandingPageController, blade views,
 * LandingPageService, and the SPA customization page are all gone — no
 * code path reads or writes these columns anymore.
 *
 * SPA branding lives in the `branding` JSON column added in
 * 2026_04_28_210907_add_branding_to_tenants_table; that's the only
 * source of truth for tenant theming going forward.
 *
 * Down migration restores nullable columns with the original defaults but
 * does NOT recreate the controllers/views/route — re-introducing the
 * landing page is a code change, not a schema rollback.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn([
                'tagline',
                'description',
                'primary_color',
                'secondary_color',
                'hero_image_path',
                'is_landing_page_active',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('tagline')->nullable()->after('logo_path');
            $table->text('description')->nullable()->after('tagline');
            $table->string('primary_color', 7)->nullable()->default('#2c3e50')->after('description');
            $table->string('secondary_color', 7)->nullable()->default('#3498db')->after('primary_color');
            $table->string('hero_image_path')->nullable()->after('secondary_color');
            $table->boolean('is_landing_page_active')->default(false)->after('hero_image_path');
        });
    }
};
