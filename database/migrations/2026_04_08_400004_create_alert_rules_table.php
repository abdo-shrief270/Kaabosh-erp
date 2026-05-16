<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('metric', 50);
            $table->string('operator', 10);
            $table->decimal('threshold', 15, 2);
            $table->string('check_frequency', 20)->default('daily');
            $table->json('notification_channels');
            $table->json('recipients');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->integer('trigger_count')->default(0);
            $table->integer('cooldown_hours')->default(24);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('alert_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('alert_rule_id')->constrained('alert_rules')->cascadeOnDelete();
            $table->datetime('triggered_at');
            $table->decimal('metric_value', 15, 2);
            $table->decimal('threshold_value', 15, 2);
            $table->text('message_ar');
            $table->text('message_en');
            $table->json('notified_users');
            $table->timestamps();

            $table->index(['tenant_id', 'triggered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_history');
        Schema::dropIfExists('alert_rules');
    }
};
