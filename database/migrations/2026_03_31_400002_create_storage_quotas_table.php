<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_quotas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained('tenants')->cascadeOnDelete();

            $table->unsignedBigInteger('max_bytes')->default(1073741824)->comment('الحد الأقصى بالبايت - 1GB افتراضياً');
            $table->unsignedBigInteger('used_bytes')->default(0)->comment('الإجمالي المستخدم بالبايت');
            $table->unsignedInteger('max_files')->default(5000)->comment('الحد الأقصى لعدد الملفات');
            $table->unsignedInteger('used_files')->default(0)->comment('عدد الملفات المستخدمة');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_quotas');
    }
};
