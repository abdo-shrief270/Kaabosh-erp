<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_actions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices');
            $table->foreignId('client_id')->constrained('clients');

            $table->string('action_type', 20)->comment('نوع إجراء التحصيل');
            $table->date('action_date');
            $table->text('notes')->nullable();
            $table->string('outcome', 20)->nullable()->comment('نتيجة الإجراء');
            $table->date('commitment_date')->nullable()->comment('تاريخ التزام العميل بالسداد');
            $table->decimal('commitment_amount', 15, 2)->nullable()->comment('مبلغ الالتزام');
            $table->foreignId('performed_by')->constrained('users');

            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'client_id']);
            $table->index(['tenant_id', 'invoice_id']);
            $table->index(['tenant_id', 'action_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_actions');
    }
};
