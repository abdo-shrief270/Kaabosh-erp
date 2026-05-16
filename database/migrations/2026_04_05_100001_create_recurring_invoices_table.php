<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Schedule
            $table->string('frequency'); // weekly, monthly, quarterly, yearly
            $table->unsignedSmallInteger('day_of_month')->nullable(); // 1-28 for monthly
            $table->unsignedSmallInteger('day_of_week')->nullable(); // 0=Sun - 6=Sat for weekly
            $table->date('start_date');
            $table->date('end_date')->nullable(); // null = indefinite
            $table->date('next_run_date');
            $table->date('last_run_date')->nullable();

            // Invoice template
            $table->json('line_items'); // [{description, quantity, unit_price, discount_percent, vat_rate, account_id}]
            $table->string('currency', 3)->default('EGP');
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->unsignedSmallInteger('due_days')->default(30);

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_send')->default(false); // auto-send after creation
            $table->unsignedInteger('invoices_generated')->default(0);
            $table->unsignedInteger('max_occurrences')->nullable(); // null = unlimited

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active', 'next_run_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_invoices');
    }
};
