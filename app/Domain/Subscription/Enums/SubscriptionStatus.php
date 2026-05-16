<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Enums;

enum SubscriptionStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case PastDue = 'past_due';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Trial => 'Trial',
            self::Active => 'Active',
            self::PastDue => 'Past Due',
            self::Cancelled => 'Cancelled',
            self::Expired => 'Expired',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::Trial => 'تجربة',
            self::Active => 'نشط',
            self::PastDue => 'متأخر الدفع',
            self::Cancelled => 'ملغى',
            self::Expired => 'منتهي',
        };
    }

    public function isAccessible(): bool
    {
        return in_array($this, [self::Trial, self::Active], true);
    }

    public function canRenew(): bool
    {
        return in_array($this, [self::Active, self::PastDue, self::Expired], true);
    }

    public function color(): string
    {
        return match ($this) {
            self::Trial => 'blue',
            self::Active => 'green',
            self::PastDue => 'yellow',
            self::Cancelled => 'red',
            self::Expired => 'gray',
        };
    }
}
