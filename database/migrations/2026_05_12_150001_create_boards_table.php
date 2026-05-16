<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 200);
            $table->string('slug', 120);
            $table->string('description', 500)->nullable();
            $table->string('color', 9)->default('#6366f1');
            $table->string('icon', 60)->nullable();
            // 'private' (creator only), 'team' (named users), 'company' (everyone)
            $table->string('visibility', 20)->default('company');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_archived')->default(false);
            // Identifier prefix for task numbers (BOARD-1, BOARD-2…)
            $table->string('key', 12)->nullable();
            $table->unsignedInteger('next_task_number')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'slug']);
            $table->unique(['tenant_id', 'key']);
            $table->index(['tenant_id', 'is_archived']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boards');
    }
};
