<?php

declare(strict_types=1);

namespace App\Domain\Tax\Enums;

enum WhtCertificateStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Submitted = 'submitted';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Issued => 'Issued',
            self::Submitted => 'Submitted',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::Draft => 'مسودة',
            self::Issued => 'صادرة',
            self::Submitted => 'مقدمة',
        };
    }
}
