<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_workflows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('entity_type', 50)->comment('bill, expense, journal_entry, leave_request, payroll_run');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'entity_type']);
        });

        Schema::create('approval_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workflow_id')->constrained('approval_workflows')->cascadeOnDelete();
            $table->smallInteger('step_order');
            $table->string('approver_type', 20)->comment('user, role, manager');
            $table->unsignedInteger('approver_id')->nullable()->comment('user_id or role_id');
            $table->decimal('approval_limit', 15, 2)->nullable()->comment('only trigger if amount exceeds');
            $table->unsignedInteger('timeout_hours')->nullable();
            $table->timestamps();

            $table->index(['workflow_id', 'step_order']);
        });

        Schema::create('approval_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('workflow_id')->constrained('approval_workflows');
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('entity_id');
            $table->smallInteger('current_step')->default(1);
            $table->string('status', 20)->default('pending')->comment('pending, in_progress, approved, rejected, cancelled');
            $table->foreignId('requested_by')->constrained('users');
            $table->timestamps();

            $table->index(['tenant_id', 'entity_type', 'entity_id']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('approval_actions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('approval_request_id')->constrained('approval_requests')->cascadeOnDelete();
            $table->smallInteger('step_order');
            $table->string('action', 20)->comment('approved, rejected, skipped');
            $table->foreignId('acted_by')->constrained('users');
            $table->text('comment')->nullable();
            $table->timestamp('acted_at');
            $table->timestamps();

            $table->index(['approval_request_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_actions');
        Schema::dropIfExists('approval_requests');
        Schema::dropIfExists('approval_steps');
        Schema::dropIfExists('approval_workflows');
    }
};
