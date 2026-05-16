<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user (or shared) saved filter presets for a board. The `filters`
 * payload is the same shape the BoardFilterBar writes to the URL —
 * applying a view just patches the query.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_board_views', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('board_id')->constrained('boards')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 120);
            // 'kanban' | 'list' | 'calendar' — applies on click.
            $table->string('view_mode', 16)->default('kanban');
            // Filter payload (q, assignee_id, priority, type, tag, version, open_only, overdue).
            $table->jsonb('filters')->nullable();
            $table->boolean('is_shared')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->double('position')->default(0);
            $table->timestamps();

            $table->index(['board_id', 'user_id']);
            $table->index(['board_id', 'is_shared']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_board_views');
    }
};
