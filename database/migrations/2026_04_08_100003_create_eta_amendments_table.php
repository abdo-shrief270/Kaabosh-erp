<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eta_amendments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('eta_document_id')->constrained('eta_documents')->cascadeOnDelete();
            $table->foreignId('corrected_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('type');           // cancellation, amendment
            $table->string('status')->default('pending');
            $table->text('reason_ar');
            $table->text('reason_en')->nullable();
            $table->timestamp('deadline_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('response_at')->nullable();
            $table->jsonb('response_data')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'type']);
            $table->index(['status', 'deadline_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eta_amendments');
    }
};
