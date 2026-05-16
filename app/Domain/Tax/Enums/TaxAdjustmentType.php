<?php

declare(strict_types=1);

namespace App\Domain\Tax\Enums;

enum TaxAdjustmentType: string
{
    case NonDeductibleExpense = 'non_deductible_expense';
    case TaxDepreciationDiff = 'tax_depreciation_diff';
    case TaxLossCarryforward = 'tax_loss_carryforward';
    case ExemptIncome = 'exempt_income';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::NonDeductibleExpense => 'Non-Deductible Expense',
            self::TaxDepreciationDiff => 'Tax Depreciation Difference',
            self::TaxLossCarryforward => 'Tax Loss Carryforward',
            self::ExemptIncome => 'Exempt Income',
            self::Other => 'Other',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::NonDeductibleExpense => 'مصروفات غير واجبة الخصم',
            self::TaxDepreciationDiff => 'فرق الإهلاك الضريبي',
            self::TaxLossCarryforward => 'خسائر مرحلة',
            self::ExemptIncome => 'إيرادات معفاة',
            self::Other => 'أخرى',
        };
    }
}
