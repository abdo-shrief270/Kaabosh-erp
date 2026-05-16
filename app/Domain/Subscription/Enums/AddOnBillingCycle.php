<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Enums;

enum AddOnBillingCycle: string
{
    case Monthly = 'monthly';
    case Annual = 'annual';
    case Once = 'once';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Annual => 'Annual',
            self::Once => 'One-time',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::Monthly => 'شهري',
            self::Annual => 'سنوي',
            self::Once => 'مرة واحدة',
        };
    }
}
