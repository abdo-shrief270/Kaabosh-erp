<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Enums;

enum PayrollStatus: string
{
    case Draft = 'draft';
    case Calculated = 'calculated';
    case Approved = 'approved';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Calculated => 'Calculated',
            self::Approved => 'Approved',
            self::Paid => 'Paid',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::Draft => 'مسودة',
            self::Calculated => 'محسوبة',
            self::Approved => 'معتمدة',
            self::Paid => 'مدفوعة',
        };
    }

    public function canCalculate(): bool
    {
        return $this === self::Draft;
    }

    public function canApprove(): bool
    {
        return $this === self::Calculated;
    }

    public function canMarkPaid(): bool
    {
        return $this === self::Approved;
    }

    public function canEdit(): bool
    {
        return $this === self::Draft;
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Calculated => 'blue',
            self::Approved => 'green',
            self::Paid => 'emerald',
        };
    }
}
