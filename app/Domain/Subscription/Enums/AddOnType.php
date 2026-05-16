<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Enums;

enum AddOnType: string
{
    /** Recurring add-on that raises one or more plan limits. */
    case Boost = 'boost';

    /** Recurring add-on that toggles a feature flag on. */
    case Feature = 'feature';

    /** One-time purchase that grants N consumable credits of a given kind. */
    case CreditPack = 'credit_pack';

    public function label(): string
    {
        return match ($this) {
            self::Boost => 'Limit boost',
            self::Feature => 'Feature unlock',
            self::CreditPack => 'Credit pack',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::Boost => 'رفع الحد',
            self::Feature => 'ميزة إضافية',
            self::CreditPack => 'باقة رصيد',
        };
    }
}
