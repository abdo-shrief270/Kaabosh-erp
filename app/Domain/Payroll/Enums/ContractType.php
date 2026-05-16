<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Enums;

enum ContractType: string
{
    case Indefinite = 'indefinite';
    case FixedTerm = 'fixed_term';
    case PartTime = 'part_time';
    case Temporary = 'temporary';

    public function label(): string
    {
        return match ($this) {
            self::Indefinite => 'Indefinite',
            self::FixedTerm => 'Fixed Term',
            self::PartTime => 'Part Time',
            self::Temporary => 'Temporary',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::Indefinite => 'غير محدد المدة',
            self::FixedTerm => 'محدد المدة',
            self::PartTime => 'دوام جزئي',
            self::Temporary => 'مؤقت',
        };
    }
}
