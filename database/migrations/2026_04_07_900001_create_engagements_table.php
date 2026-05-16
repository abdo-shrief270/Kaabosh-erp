<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('engagements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients');
            $table->foreignId('fiscal_year_id')->nullable()->constrained('fiscal_years');
            $table->string('engagement_type', 20)->comment('audit, review, compilation, tax, bookkeeping, consulting');
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('status', 20)->default('planning')->comment('planning, in_progress, review, completed, archived');
            $table->foreignId('manager_id')->nullable()->constrained('users');
            $table->foreignId('partner_id')->nullable()->constrained('users');
            $table->decimal('planned_hours', 5, 2)->default(0);
            $table->decimal('actual_hours', 5, 2)->default(0);
            $table->decimal('budget_amount', 15, 2)->default(0);
            $table->decimal('actual_amount', 15, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('deadline')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'client_id']);
        });

        Schema::create('working_papers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('engagement_id')->constrained('engagements')->cascadeOnDelete();
            $table->string('section', 50)->comment('e.g. A-Planning, B-Execution, C-Conclusion');
            $table->string('reference_code', 30)->nullable();
            $table->string('title_ar');
            $table->string('title_en')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('not_started')->comment('not_started, in_progress, completed, reviewed');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('document_id')->nullable()->constrained('documents');
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['engagement_id', 'section']);
        });

        Schema::create('engagement_deliverables', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('engagement_id')->constrained('engagements')->cascadeOnDelete();
            $table->string('title_ar');
            $table->string('title_en')->nullable();
            $table->date('due_date')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('engagement_deliverables');
        Schema::dropIfExists('working_papers');
        Schema::dropIfExists('engagements');
    }
};
