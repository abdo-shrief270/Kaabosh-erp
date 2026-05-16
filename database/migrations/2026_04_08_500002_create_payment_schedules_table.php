<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bill_id')->nullable()->constrained('bills')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->date('scheduled_date');
            $table->decimal('amount', 15, 2);
            $table->string('status', 20)->default('pending');
            $table->string('payment_method', 20)->nullable();
            $table->decimal('early_discount_percent', 5, 2)->default(0);
            $table->date('early_discount_deadline')->nullable();
            $table->decimal('early_discount_amount', 15, 2)->default(0);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'status', 'scheduled_date']);
            $table->index(['tenant_id', 'bill_id']);
        });

        Schema::create('auto_approval_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('entity_type', 20);
            $table->string('condition_field', 50);
            $table->string('operator', 10);
            $table->string('condition_value', 255);
            $table->string('auto_action', 20);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'entity_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_approval_rules');
        Schema::dropIfExists('payment_schedules');
    }
};
