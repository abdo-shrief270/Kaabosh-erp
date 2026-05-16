<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Task templates — pre-filled blueprints for quickly creating common task
 * shapes (e.g. "bug-report", "release-checklist", "onboarding-doc"). The
 * template can target a specific board or be company-wide (board_id NULL).
 *
 * Defaults live as JSONB so adding new task fields later doesn't require
 * schema migrations on the templates table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('board_id')->nullable()->constrained('boards')->nullOnDelete();
            $table->foreignId('task_type_id')->nullable()->constrained('task_types')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('name', 120);
            $table->string('icon', 60)->nullable();
            $table->string('description', 500)->nullable();

            // Template fields applied when a task is created from the template.
            $table->string('title_template', 500);
            $table->longText('body_template')->nullable();
            $table->string('priority', 16)->default('medium');
            $table->decimal('default_estimate_hours', 8, 2)->nullable();
            $table->jsonb('default_tag_ids')->nullable();
            $table->jsonb('default_checklist')->nullable(); // ["text 1", "text 2", ...]

            $table->boolean('is_system')->default(false);
            $table->unsignedInteger('use_count')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'board_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_templates');
    }
};
