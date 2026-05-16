<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Enums;

enum OvertimeType: string
{
    case Weekday = 'weekday';
    case Friday = 'friday';
    case Holiday = 'holiday';
    case Night = 'night';

    public function label(): string
    {
        return match ($this) {
            self::Weekday => 'Weekday',
            self::Friday => 'Friday',
            self::Holiday => 'Holiday',
            self::Night => 'Night',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::Weekday => 'يوم عمل',
            self::Friday => 'الجمعة',
            self::Holiday => 'عطلة رسمية',
            self::Night => 'ليلي',
        };
    }

    /**
     * Overtime multiplier rate (bcmath compatible).
     */
    public function rate(): string
    {
        return match ($this) {
            self::Weekday => '1.35',
            self::Friday => '2.00',
            self::Holiday => '2.00',
            self::Night => '1.50',
        };
    }
}
