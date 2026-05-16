<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';         // Tenant owner / accounting firm admin
    case Accountant = 'accountant';
    case Auditor = 'auditor';
    case Client = 'client';       // End-client of the accounting firm

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Accountant => 'Accountant',
            self::Auditor => 'Auditor',
            self::Client => 'Client',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::SuperAdmin => 'مدير النظام',
            self::Admin => 'مدير',
            self::Accountant => 'محاسب',
            self::Auditor => 'مراجع',
            self::Client => 'عميل',
        };
    }

    public function isTenantLevel(): bool
    {
        return in_array($this, [self::Admin, self::Accountant, self::Auditor, self::Client]);
    }

    public function isPlatformLevel(): bool
    {
        return $this === self::SuperAdmin;
    }
}
