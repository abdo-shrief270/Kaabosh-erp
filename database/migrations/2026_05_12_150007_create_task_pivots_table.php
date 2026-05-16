<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_assignees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->useCurrent();

            $table->unique(['task_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::create('task_watchers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['task_id', 'user_id']);
        });

        Schema::create('task_tag', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();

            $table->unique(['task_id', 'tag_id']);
            $table->index('tag_id');
        });

        Schema::create('task_version', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('version_id')->constrained('versions')->cascadeOnDelete();

            $table->unique(['task_id', 'version_id']);
            $table->index('version_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_version');
        Schema::dropIfExists('task_tag');
        Schema::dropIfExists('task_watchers');
        Schema::dropIfExists('task_assignees');
    }
};
