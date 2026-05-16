<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Enums;

enum LoanType: string
{
    case SalaryAdvance = 'salary_advance';
    case PersonalLoan = 'personal_loan';
    case HousingLoan = 'housing_loan';

    public function label(): string
    {
        return match ($this) {
            self::SalaryAdvance => 'Salary Advance',
            self::PersonalLoan => 'Personal Loan',
            self::HousingLoan => 'Housing Loan',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::SalaryAdvance => 'سلفة راتب',
            self::PersonalLoan => 'قرض شخصي',
            self::HousingLoan => 'قرض سكني',
        };
    }
}
