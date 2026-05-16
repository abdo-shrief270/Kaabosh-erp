<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_recurrences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('board_id')->constrained('boards')->cascadeOnDelete();
            $table->foreignId('board_column_id')->constrained('board_columns')->restrictOnDelete();
            $table->foreignId('task_type_id')->constrained('task_types')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Template fields — used to spawn each occurrence.
            $table->string('title', 500);
            $table->longText('description')->nullable();
            $table->string('priority', 16)->default('medium');
            $table->foreignId('default_assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->jsonb('default_tag_ids')->nullable();

            // Recurrence pattern: 'daily' | 'weekly' | 'monthly' | 'yearly' | 'cron' (advanced)
            $table->string('frequency', 12);
            $table->unsignedSmallInteger('interval')->default(1); // every N units
            // For weekly: [1,3,5] = Mon/Wed/Fri (1=Mon … 7=Sun). For monthly: [1,15] = 1st & 15th.
            $table->jsonb('byday')->nullable();
            $table->string('cron_expression', 80)->nullable();
            $table->string('timezone', 60)->default('Africa/Cairo');

            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('max_occurrences')->nullable();
            $table->unsignedInteger('spawned_count')->default(0);
            $table->timestamp('next_spawn_at')->nullable();
            $table->timestamp('last_spawned_at')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active', 'next_spawn_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_recurrences');
    }
};
