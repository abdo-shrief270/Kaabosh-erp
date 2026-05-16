<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();

            $table->string('description', 500);
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(14.00)->comment('نسبة ضريبة القيمة المضافة');
            $table->decimal('line_total', 15, 2)->default(0)->comment('(qty * unit_price) - discount');
            $table->decimal('vat_amount', 15, 2)->default(0)->comment('line_total * vat_rate / 100');
            $table->decimal('total', 15, 2)->default(0)->comment('line_total + vat_amount');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();

            $table->timestamps();

            // Indexes
            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
    }
};
