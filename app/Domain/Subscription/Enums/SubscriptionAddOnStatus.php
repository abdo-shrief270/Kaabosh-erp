<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Enums;

enum SubscriptionAddOnStatus: string
{
    /** Awaiting gateway capture — created from an online purchase, not yet usable. */
    case Pending = 'pending';
    case Active = 'active';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    /** Gateway rejected the payment after a pending purchase. */
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending payment',
            self::Active => 'Active',
            self::Cancelled => 'Cancelled',
            self::Expired => 'Expired',
            self::Failed => 'Payment failed',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::Pending => 'بانتظار الدفع',
            self::Active => 'نشط',
            self::Cancelled => 'مُلغى',
            self::Expired => 'منتهي',
            self::Failed => 'فشل الدفع',
        };
    }
}
