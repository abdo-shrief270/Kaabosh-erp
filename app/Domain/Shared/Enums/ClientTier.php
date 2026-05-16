<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum ClientTier: string
{
    case Micro    = 'micro';
    case Small    = 'small';
    case Standard = 'standard';
    case Large    = 'large';

    public function label(): string
    {
        return match ($this) {
            self::Micro    => 'Micro',
            self::Small    => 'Small',
            self::Standard => 'Standard',
            self::Large    => 'Large',
        };
    }

    /**
     * Maximum invoices + bills posted per calendar month.
     */
    public function monthlyTransactionLimit(): int
    {
        return match ($this) {
            self::Micro    => 20,
            self::Small    => 100,
            self::Standard => 500,
            self::Large    => 2_000,
        };
    }

    /**
     * Monthly price in EGP, charged to the firm per active client-tenant.
     */
    public function monthlyPriceEgp(): int
    {
        return match ($this) {
            self::Micro    => 49,
            self::Small    => 99,
            self::Standard => 199,
            self::Large    => 499,
        };
    }
}
