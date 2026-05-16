<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Automation rules. Each row is one "when X happens AND Y AND Z, do these
 * actions" pipeline scoped to a board (or company-wide when board_id is null).
 *
 * Triggers, conditions and actions are stored as JSONB so adding new ones
 * is a code-only change. The evaluator service knows the schema for each:
 *
 *  trigger_type:  task_created | task_moved | task_completed | task_assigned
 *  trigger_config (per type):
 *    task_moved      -> {from_column_id?: int, to_column_id?: int}
 *    task_completed  -> {} (no params; fires when entering any done column)
 *    task_assigned   -> {user_id?: int}
 *
 *  conditions: [{field, op, value}, ...] — ALL must match (AND).
 *  fields: priority | task_type_id | tag_id | assignee_id
 *  ops:    is | not | in | not_in | has | has_not
 *
 *  actions: [{type, payload}, ...]
 *    move_to_column   -> {column_id}
 *    assign_to        -> {user_id, mode: 'replace'|'add'}
 *    add_tag          -> {tag_id}
 *    remove_tag       -> {tag_id}
 *    set_priority     -> {priority}
 *    post_comment     -> {body}    // supports {{actor}} {{task_title}} tokens
 *    add_to_sprint    -> {sprint_id}
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('board_id')->nullable()->constrained('boards')->cascadeOnDelete();
            $table->string('name', 200);
            $table->string('description', 500)->nullable();
            $table->boolean('is_active')->default(true);

            $table->string('trigger_type', 32);
            $table->jsonb('trigger_config')->nullable();
            $table->jsonb('conditions')->nullable();
            $table->jsonb('actions')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_fired_at')->nullable();
            $table->unsignedInteger('fire_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'board_id', 'trigger_type', 'is_active'], 'idx_automation_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_rules');
    }
};
