<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_connections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('bank_code', 20);
            $table->string('account_number', 50);
            $table->string('iban', 34)->nullable();
            $table->string('account_name')->nullable();
            $table->string('currency', 3)->default('EGP');
            $table->string('connection_type', 20)->default('manual');
            $table->text('api_credentials')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->string('sync_status', 20)->default('disconnected');
            $table->decimal('balance', 15, 2)->nullable();
            $table->date('balance_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('linked_gl_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'bank_code', 'account_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_connections');
    }
};
