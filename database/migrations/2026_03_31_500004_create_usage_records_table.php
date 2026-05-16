<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->date('recorded_at');
            $table->unsignedInteger('users_count')->default(0);
            $table->unsignedInteger('clients_count')->default(0);
            $table->unsignedInteger('invoices_count')->default(0)->comment('عدد الفواتير هذا الشهر');
            $table->unsignedBigInteger('storage_bytes')->default(0);
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('tenant_id');
            $table->index('recorded_at');
            $table->unique(['tenant_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_records');
    }
};
