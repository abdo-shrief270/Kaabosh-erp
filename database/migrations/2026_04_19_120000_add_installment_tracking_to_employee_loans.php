<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Original create_employee_loans migration tracked the loan as a single
 * balance. The service layer and model evolved to also track installment
 * counts and the approval timestamp — add the missing columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_loans', function (Blueprint $table): void {
            if (! Schema::hasColumn('employee_loans', 'total_installments')) {
                $table->unsignedInteger('total_installments')->default(0)->after('installment_amount');
            }
            if (! Schema::hasColumn('employee_loans', 'paid_installments')) {
                $table->unsignedInteger('paid_installments')->default(0)->after('total_installments');
            }
            if (! Schema::hasColumn('employee_loans', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_loans', function (Blueprint $table): void {
            $table->dropColumn(['total_installments', 'paid_installments', 'approved_at']);
        });
    }
};
