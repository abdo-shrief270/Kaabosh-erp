<?php

declare(strict_types=1);

namespace App\Domain\Tax\Enums;

enum TaxReturnType: string
{
    case CorporateTax = 'corporate_tax';
    case VatMonthly = 'vat_monthly';
    case VatQuarterly = 'vat_quarterly';

    public function label(): string
    {
        return match ($this) {
            self::CorporateTax => 'Corporate Tax',
            self::VatMonthly => 'VAT Monthly',
            self::VatQuarterly => 'VAT Quarterly',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::CorporateTax => 'ضريبة الشركات',
            self::VatMonthly => 'ضريبة القيمة المضافة شهري',
            self::VatQuarterly => 'ضريبة القيمة المضافة ربع سنوي',
        };
    }
}
