<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * bank_accounts — the company's own bank accounts. Distinct from the
 * `accounts` chart-of-accounts (which carries the GL row); each bank
 * account points at a GL cash account via `gl_account_id` so payments
 * recorded against it can post to the right ledger account.
 *
 * Why a separate table instead of just using the chart of accounts:
 *   - Carries operational metadata (IBAN, SWIFT, branch) that doesn't
 *     belong on a GL row
 *   - Lets the same GL cash account be split into multiple operational
 *     bank accounts (e.g. multi-currency sub-accounts)
 *   - Makes "is this a bank account?" a fast lookup instead of a
 *     join + type filter on accounts
 *
 * The expense reimbursement flow + bill payments + future receipts UI
 * all populate their bank-account picker from this table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->string('account_name', 200);
            $table->string('bank_name', 200);
            $table->string('branch', 200)->nullable();
            $table->string('account_number', 50)->nullable();
            $table->string('iban', 34)->nullable();
            $table->string('swift_code', 11)->nullable();
            $table->string('currency', 3)->default('EGP');

            // FK to the GL cash account this bank account posts to. nullOnDelete
            // because losing the link silently is preferable to cascading away
            // the entire bank account when a GL account is removed — the
            // operator can re-link instead.
            $table->foreignId('gl_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();

            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active']);
            $table->unique(['tenant_id', 'iban']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
