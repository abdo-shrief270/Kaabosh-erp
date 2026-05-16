<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Enums;

enum PaymentGateway: string
{
    case Paymob = 'paymob';
    case Fawry = 'fawry';
    case Stripe = 'stripe';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Paymob => 'Paymob',
            self::Fawry => 'Fawry',
            self::Stripe => 'Stripe',
            self::Manual => 'Manual',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::Paymob => 'باي موب',
            self::Fawry => 'فوري',
            self::Stripe => 'سترايب',
            self::Manual => 'يدوي',
        };
    }
}
