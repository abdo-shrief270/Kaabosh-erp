<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Enums;

enum SalaryComponentType: string
{
    case Allowance = 'allowance';
    case Deduction = 'deduction';
    case Contribution = 'contribution';

    public function label(): string
    {
        return match ($this) {
            self::Allowance => 'Allowance',
            self::Deduction => 'Deduction',
            self::Contribution => 'Contribution',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::Allowance => 'بدل',
            self::Deduction => 'خصم',
            self::Contribution => 'اشتراك',
        };
    }
}
