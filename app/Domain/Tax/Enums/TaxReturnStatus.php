<?php

declare(strict_types=1);

namespace App\Domain\Tax\Enums;

enum TaxReturnStatus: string
{
    case Draft = 'draft';
    case Calculated = 'calculated';
    case Filed = 'filed';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Calculated => 'Calculated',
            self::Filed => 'Filed',
            self::Paid => 'Paid',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::Draft => 'مسودة',
            self::Calculated => 'محسوب',
            self::Filed => 'مقدم',
            self::Paid => 'مدفوع',
        };
    }

    public function canCalculate(): bool
    {
        return $this === self::Draft;
    }

    public function canFile(): bool
    {
        return $this === self::Calculated;
    }

    public function canPay(): bool
    {
        return $this === self::Filed;
    }
}
