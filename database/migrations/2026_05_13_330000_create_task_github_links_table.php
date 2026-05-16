<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_github_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('repo', 240);          // "owner/repo"
            $table->unsignedInteger('pr_number');
            $table->string('url', 500);
            // Cached state: open|closed|merged|draft|unknown
            $table->string('state', 12)->default('unknown');
            $table->string('title', 500)->nullable();
            $table->string('author', 80)->nullable();
            $table->string('etag', 80)->nullable();   // GitHub ETag for conditional GETs
            $table->timestamp('last_fetched_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // A task can't link the same PR twice.
            $table->unique(['task_id', 'repo', 'pr_number']);
            $table->index('last_fetched_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_github_links');
    }
};
