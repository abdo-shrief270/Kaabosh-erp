<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Individual feature cards within a showcase section. The optional
 * `badge` flags entries the marketing site should highlight — "Hidden",
 * "New", "Pro", etc. — and renders as a pill on the card.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_showcase_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('section_id')
                ->constrained('feature_showcase_sections')
                ->cascadeOnDelete();

            $table->string('icon', 64)->nullable();

            $table->string('title_en', 160);
            $table->string('title_ar', 160);
            $table->text('description_en');
            $table->text('description_ar');

            $table->string('badge_en', 32)->nullable();
            $table->string('badge_ar', 32)->nullable();

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['section_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_showcase_items');
    }
};
