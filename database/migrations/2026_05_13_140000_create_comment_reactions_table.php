<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comment-scoped reactions. Per user × emoji uniqueness so toggling a
 * second time removes the row, mirroring Slack/Linear behaviour. We
 * deliberately store the emoji as a string (UTF-8 codepoints) instead of
 * a shortcode set — keeps the picker open and avoids a translation table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comment_reactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('comment_id')->constrained('task_comments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('emoji', 16);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['comment_id', 'user_id', 'emoji']);
            $table->index(['comment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_reactions');
    }
};
