<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_board_webhooks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            // NULL board_id = tenant-wide subscription (every board's events).
            $table->foreignId('board_id')->nullable()->constrained('boards')->cascadeOnDelete();
            $table->string('label', 120);
            $table->string('url', 500);
            // Format: 'auto' (detect from url), 'slack', 'discord', 'generic'.
            $table->string('format', 10)->default('auto');
            // List of event keys this hook cares about (e.g. ['task.created', 'task.moved']).
            $table->jsonb('events');
            $table->string('secret', 80)->nullable();         // HMAC signing for generic format
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_succeeded_at')->nullable();
            $table->timestamp('last_failed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
            $table->index(['board_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_board_webhooks');
    }
};
