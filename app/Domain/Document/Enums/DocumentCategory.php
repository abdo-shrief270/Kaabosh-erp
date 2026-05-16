<?php

declare(strict_types=1);

namespace App\Domain\Document\Enums;

enum DocumentCategory: string
{
    case TaxDocument = 'tax_document';
    case Invoice = 'invoice';
    case Receipt = 'receipt';
    case Contract = 'contract';
    case FinancialStatement = 'financial_statement';
    case Correspondence = 'correspondence';
    case WorkingPaper = 'working_paper';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::TaxDocument => 'Tax Document',
            self::Invoice => 'Invoice',
            self::Receipt => 'Receipt',
            self::Contract => 'Contract',
            self::FinancialStatement => 'Financial Statement',
            self::Correspondence => 'Correspondence',
            self::WorkingPaper => 'Working Paper',
            self::Other => 'Other',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::TaxDocument => 'مستند ضريبي',
            self::Invoice => 'فاتورة',
            self::Receipt => 'إيصال',
            self::Contract => 'عقد',
            self::FinancialStatement => 'قائمة مالية',
            self::Correspondence => 'مراسلات',
            self::WorkingPaper => 'ورقة عمل',
            self::Other => 'أخرى',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::TaxDocument => 'file-text',
            self::Invoice => 'file-invoice',
            self::Receipt => 'receipt',
            self::Contract => 'file-contract',
            self::FinancialStatement => 'chart-bar',
            self::Correspondence => 'mail',
            self::WorkingPaper => 'clipboard',
            self::Other => 'file',
        };
    }
}
