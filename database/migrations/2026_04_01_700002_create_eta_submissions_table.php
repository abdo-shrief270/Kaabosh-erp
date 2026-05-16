<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eta_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('submission_uuid', 100)->nullable()->unique();
            $table->string('status', 20)->default('pending');
            $table->unsignedSmallInteger('document_count')->default(0);
            $table->unsignedSmallInteger('accepted_count')->default(0);
            $table->unsignedSmallInteger('rejected_count')->default(0);
            $table->json('response_data')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eta_submissions');
    }
};
