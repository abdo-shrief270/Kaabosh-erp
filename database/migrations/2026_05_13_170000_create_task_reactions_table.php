<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reactions on tasks themselves (mirroring comment_reactions). Per-user ×
 * emoji uniqueness so a second click toggles the row off — same UX as
 * Slack/Linear. Kept narrow on purpose: only the task itself, not arbitrary
 * "reactable" polymorphism.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_reactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('emoji', 16);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['task_id', 'user_id', 'emoji']);
            $table->index('task_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_reactions');
    }
};
