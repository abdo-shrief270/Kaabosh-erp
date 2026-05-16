<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();

            $table->string('code', 20)->comment('رقم الحساب');
            $table->string('name_ar')->comment('اسم الحساب بالعربية');
            $table->string('name_en')->nullable()->comment('اسم الحساب بالإنجليزية');
            $table->string('type', 20)->comment('نوع الحساب');
            $table->string('normal_balance', 10)->comment('الطبيعة المدينة أو الدائنة');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_group')->default(false)->comment('حساب رئيسي لا يقبل قيود');
            $table->unsignedTinyInteger('level')->default(1)->comment('المستوى في الشجرة');
            $table->text('description')->nullable();
            $table->string('currency', 3)->default('EGP');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('tenant_id');
            $table->index('parent_id');
            $table->index('type');
            $table->index('is_active');
            $table->unique(['tenant_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
