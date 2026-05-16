<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Allow-listed column transitions per (board, task_type). When a
        // task_type has ANY rules defined on a board, only edges in this
        // table are permitted. No rules = any transition allowed (the
        // back-compat default that keeps existing boards unchanged).
        Schema::create('task_workflow_transitions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('board_id')->constrained('boards')->cascadeOnDelete();
            $table->foreignId('task_type_id')->constrained('task_types')->cascadeOnDelete();
            $table->foreignId('from_column_id')->constrained('board_columns')->cascadeOnDelete();
            $table->foreignId('to_column_id')->constrained('board_columns')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['board_id', 'task_type_id', 'from_column_id', 'to_column_id'], 'workflow_unique_edge');
            // Hot path: "what transitions are allowed for type T leaving column F?"
            $table->index(['board_id', 'task_type_id', 'from_column_id'], 'workflow_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_workflow_transitions');
    }
};
