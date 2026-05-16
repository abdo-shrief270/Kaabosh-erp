<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_reminders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('remind_at');                  // when to fire
            $table->string('note', 240)->nullable();         // optional message
            $table->timestamp('sent_at')->nullable();        // idempotency
            $table->timestamps();

            // Hot path: "find due reminders to send right now".
            $table->index(['remind_at', 'sent_at']);
            // Hot path: "list a user's reminders on a task".
            $table->index(['task_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_reminders');
    }
};
