<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprints — timeboxed task groups beyond versions. A board has at most one
 * sprint in 'active' state at a time; transitions are enforced application-
 * side so the invariant is auditable in logs.
 *
 * The `committed_estimate_hours` snapshot freezes the planned-at-start
 * size so velocity calculations don't drift when tasks are estimated
 * after the sprint starts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sprints', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('board_id')->constrained('boards')->cascadeOnDelete();
            $table->string('name', 120);
            // planned | active | completed | cancelled
            $table->string('status', 12)->default('planned');
            $table->string('goal', 500)->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            // Frozen at sprint start; used as the denominator for velocity %.
            $table->decimal('committed_estimate_hours', 10, 2)->default(0);
            $table->unsignedInteger('committed_task_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['board_id', 'status']);
            $table->index('tenant_id');
        });

        Schema::create('sprint_task', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sprint_id')->constrained('sprints')->cascadeOnDelete();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('added_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('added_at')->useCurrent();

            $table->unique(['sprint_id', 'task_id']);
            $table->index('task_id');
        });

        Schema::create('sprint_burndown_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sprint_id')->constrained('sprints')->cascadeOnDelete();
            $table->date('snapshot_date');
            $table->decimal('remaining_estimate_hours', 10, 2)->default(0);
            $table->unsignedInteger('remaining_task_count')->default(0);
            $table->unsignedInteger('completed_task_count')->default(0);
            $table->timestamps();

            $table->unique(['sprint_id', 'snapshot_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sprint_burndown_snapshots');
        Schema::dropIfExists('sprint_task');
        Schema::dropIfExists('sprints');
    }
};
