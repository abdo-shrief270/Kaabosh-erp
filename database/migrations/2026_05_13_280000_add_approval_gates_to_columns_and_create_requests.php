<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('board_columns', function (Blueprint $table): void {
            $table->boolean('requires_approval')->default(false)->after('enforce_wip');
        });

        Schema::create('task_approval_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            // from_column_id is recorded for traceability — the task may not
            // still be in this column by the time the request is decided
            // (e.g. moved elsewhere first, in which case we reject).
            $table->foreignId('from_column_id')->nullable()->constrained('board_columns')->nullOnDelete();
            $table->foreignId('target_column_id')->constrained('board_columns')->cascadeOnDelete();
            $table->foreignId('requested_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 10)->default('pending'); // pending|approved|rejected|cancelled
            $table->foreignId('decided_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->string('reason', 500)->nullable(); // approver / requester note
            $table->timestamps();

            // Hot path: "what's pending for this user / board?"
            $table->index(['status', 'tenant_id']);
            $table->index(['task_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_approval_requests');
        Schema::table('board_columns', function (Blueprint $table): void {
            $table->dropColumn('requires_approval');
        });
    }
};
