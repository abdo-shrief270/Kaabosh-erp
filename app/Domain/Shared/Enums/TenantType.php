<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum TenantType: string
{
    case FirmBooks   = 'firm_books';
    case ClientBooks = 'client_books';

    public function label(): string
    {
        return match ($this) {
            self::FirmBooks   => "Firm's Own Books",
            self::ClientBooks => 'Client Books',
        };
    }
}
