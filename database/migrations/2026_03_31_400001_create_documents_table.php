<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();

            $table->string('name', 255)->comment('اسم الملف الأصلي');
            $table->string('disk', 20)->default('local')->comment('قرص التخزين');
            $table->string('path', 1000)->comment('المسار النسبي في التخزين');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->string('hash', 64)->comment('SHA-256 للكشف عن التكرار');
            $table->string('category', 30)->default('other')->comment('تصنيف المستند');
            $table->string('storage_tier', 10)->default('hot')->comment('مستوى التخزين');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable()->comment('بيانات إضافية مرنة');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_archived')->default(false);
            $table->timestamp('archived_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('tenant_id');
            $table->index('client_id');
            $table->index('category');
            $table->index('hash');
            $table->index('uploaded_by');
            $table->index('is_archived');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
