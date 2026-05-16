<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum FirmRole: string
{
    case Owner      = 'owner';
    case Partner    = 'partner';
    case Manager    = 'manager';
    case Accountant = 'accountant';
    case Viewer     = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Owner      => 'Owner',
            self::Partner    => 'Partner',
            self::Manager    => 'Manager',
            self::Accountant => 'Accountant',
            self::Viewer     => 'Viewer',
        };
    }

    /**
     * Roles that get firm-wide visibility over every client-tenant. Lower
     * roles (Accountant, Viewer) only see the client-tenants they've been
     * explicitly assigned to via the firm_user_tenant pivot.
     */
    public function hasFirmWideAccess(): bool
    {
        return match ($this) {
            self::Owner, self::Partner, self::Manager => true,
            self::Accountant, self::Viewer            => false,
        };
    }
}
