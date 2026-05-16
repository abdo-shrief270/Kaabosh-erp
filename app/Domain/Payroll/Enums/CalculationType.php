<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Enums;

enum CalculationType: string
{
    case Fixed = 'fixed';
    case PercentageOfBasic = 'percentage_of_basic';
    case PercentageOfGross = 'percentage_of_gross';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'Fixed',
            self::PercentageOfBasic => 'Percentage of Basic',
            self::PercentageOfGross => 'Percentage of Gross',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::Fixed => 'ثابت',
            self::PercentageOfBasic => 'نسبة من الأساسي',
            self::PercentageOfGross => 'نسبة من الإجمالي',
        };
    }
}
