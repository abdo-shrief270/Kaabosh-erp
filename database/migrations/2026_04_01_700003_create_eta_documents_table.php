<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eta_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices');
            $table->foreignId('eta_submission_id')->nullable()->constrained('eta_submissions')->nullOnDelete();
            $table->string('document_type', 1);
            $table->string('internal_id', 100)->nullable();
            $table->string('eta_uuid', 100)->nullable()->unique();
            $table->string('eta_long_id', 255)->nullable();
            $table->string('status', 20)->default('prepared');
            $table->text('signed_data')->nullable();
            $table->json('document_data')->nullable();
            $table->json('eta_response')->nullable();
            $table->json('errors')->nullable();
            $table->text('qr_code_data')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'invoice_id']);
            $table->index('tenant_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eta_documents');
    }
};
