<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Enums;

enum DiscountType: string
{
    case Percent = 'percent';
    case Fixed = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::Percent => 'Percent off',
            self::Fixed => 'Fixed amount off',
        };
    }
}
