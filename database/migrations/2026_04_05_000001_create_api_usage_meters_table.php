<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_usage_meters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedBigInteger('api_calls')->default(0);
            $table->unsignedBigInteger('invoices_created')->default(0);
            $table->unsignedBigInteger('journal_entries_created')->default(0);
            $table->unsignedBigInteger('documents_uploaded')->default(0);
            $table->unsignedBigInteger('eta_submissions')->default(0);
            $table->unsignedBigInteger('emails_sent')->default(0);
            $table->unsignedBigInteger('storage_bytes')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'date']);
            $table->index(['tenant_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_usage_meters');
    }
};
