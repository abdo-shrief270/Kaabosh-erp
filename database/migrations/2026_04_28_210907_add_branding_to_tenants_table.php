<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            // Per-tenant theme: colors, typography, shape, motion. Stored as
            // JSON so the schema can evolve (new knobs added in code) without
            // a migration. Null = use platform defaults from tokens.css.
            $table->json('branding')->nullable()->after('custom_css');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn('branding');
        });
    }
};
