<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sections of the marketing-page feature showcase. Each section groups a
 * cluster of related capabilities (Core Accounting, Compliance, Hidden
 * features, Tech stack, …) and renders as one block on /features.
 *
 * Bilingual columns keep AR + EN co-located so the public endpoint can
 * return both shapes in a single query and the SPA picks based on locale.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_showcase_sections', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 64)->unique();

            $table->string('icon', 64)->nullable()->comment('Lucide icon id, e.g. i-lucide-layers');
            // `accent` keys a tinted background/border color in the SPA
            // ("primary" | "info" | "success" | "warning" | "danger").
            $table->string('accent', 16)->default('primary');

            $table->string('title_en', 120);
            $table->string('title_ar', 120);
            $table->text('subtitle_en')->nullable();
            $table->text('subtitle_ar')->nullable();

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_showcase_sections');
    }
};
