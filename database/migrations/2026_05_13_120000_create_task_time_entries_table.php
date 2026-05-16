<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Task-board-native time entries. Decoupled from the legacy `timers` table
 * (which was billable-hours-shaped for accounting firms). Here each row is
 * one start/stop session; running sessions have stopped_at = NULL.
 *
 * Constraint: at most ONE running entry per user. Enforced application-side
 * in TaskTimerService — partial-unique indexes work in Postgres but our
 * minimum bar is keeping it readable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_time_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('stopped_at')->nullable();
            // Cached on stop for fast roll-up sums without TIMESTAMPDIFF.
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('description', 500)->nullable();
            $table->timestamps();

            $table->index(['task_id', 'started_at']);
            $table->index(['user_id', 'stopped_at']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_time_entries');
    }
};
