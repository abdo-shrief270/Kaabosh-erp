<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_board_webhook_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('webhook_id')->constrained('task_board_webhooks')->cascadeOnDelete();
            $table->string('event_key', 60);
            $table->jsonb('payload');                         // shaped body we POSTed
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->string('response_body_excerpt', 2000)->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->boolean('succeeded')->default(false);
            $table->string('error', 1000)->nullable();
            $table->timestamp('delivered_at')->useCurrent();

            $table->index(['webhook_id', 'delivered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_board_webhook_deliveries');
    }
};
