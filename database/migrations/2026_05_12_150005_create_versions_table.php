<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            // Versions are board-scoped; cross-board fix-versions get separate rows.
            $table->foreignId('board_id')->constrained('boards')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('slug', 120);
            $table->string('description', 500)->nullable();
            // planned | in_progress | released | archived
            $table->string('status', 20)->default('planned');
            $table->date('release_date')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->string('color', 9)->nullable();
            $table->timestamps();

            $table->unique(['board_id', 'slug']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('versions');
    }
};
