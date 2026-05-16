<?php

declare(strict_types=1);

use App\Domain\Payroll\Services\EgyptianTaxService;

describe('calculateIncomeTax', function (): void {

    it('returns zero tax for income below 40k', function (): void {
        expect(EgyptianTaxService::calculateIncomeTax('0'))->toBe('0.00');
        expect(EgyptianTaxService::calculateIncomeTax('30000'))->toBe('0.00');
        expect(EgyptianTaxService::calculateIncomeTax('40000'))->toBe('0.00');
    });

    it('calculates 10% bracket (40k-55k)', function (): void {
        // 50,000: first 40k at 0%, next 10k at 10% = 1,000
        expect(EgyptianTaxService::calculateIncomeTax('50000'))->toBe('1000.00');

        // 55,000: first 40k at 0%, next 15k at 10% = 1,500
        expect(EgyptianTaxService::calculateIncomeTax('55000'))->toBe('1500.00');
    });

    it('calculates 15% bracket (55k-70k)', function (): void {
        // 70,000: 0 + 1,500 + (15,000 * 0.15) = 0 + 1,500 + 2,250 = 3,750
        expect(EgyptianTaxService::calculateIncomeTax('70000'))->toBe('3750.00');
    });

    it('calculates 20% bracket (70k-200k)', function (): void {
        // 200,000: 0 + 1,500 + 2,250 + (130,000 * 0.20) = 3,750 + 26,000 = 29,750
        expect(EgyptianTaxService::calculateIncomeTax('200000'))->toBe('29750.00');
    });

    it('calculates 22.5% bracket (200k-400k)', function (): void {
        // 400,000: 29,750 + (200,000 * 0.225) = 29,750 + 45,000 = 74,750
        expect(EgyptianTaxService::calculateIncomeTax('400000'))->toBe('74750.00');
    });

    it('calculates 25% bracket (400k-600k)', function (): void {
        // 600,000: 74,750 + (200,000 * 0.25) = 74,750 + 50,000 = 124,750
        expect(EgyptianTaxService::calculateIncomeTax('600000'))->toBe('124750.00');
    });

    it('calculates 27.5% bracket (above 600k)', function (): void {
        // 700,000: 124,750 + (100,000 * 0.275) = 124,750 + 27,500 = 152,250
        expect(EgyptianTaxService::calculateIncomeTax('700000'))->toBe('152250.00');
    });

    it('handles negative income', function (): void {
        expect(EgyptianTaxService::calculateIncomeTax('-5000'))->toBe('0.00');
    });
});

describe('calculateSocialInsurance', function (): void {

    it('returns zero when not insured', function (): void {
        $result = EgyptianTaxService::calculateSocialInsurance('10000', false);
        expect($result['employee_share'])->toBe('0.00');
        expect($result['employer_share'])->toBe('0.00');
    });

    it('calculates correctly for salary below ceiling', function (): void {
        $result = EgyptianTaxService::calculateSocialInsurance('5000', true);
        // Employee: 5000 * 0.11 = 550
        // Employer: 5000 * 0.1875 = 937.50
        expect($result['employee_share'])->toBe('550.00');
        expect($result['employer_share'])->toBe('937.50');
    });

    it('caps at ceiling for high salaries', function (): void {
        $result = EgyptianTaxService::calculateSocialInsurance('50000', true);
        // Capped at 12,600
        // Employee: 12600 * 0.11 = 1386
        // Employer: 12600 * 0.1875 = 2362.50
        expect($result['employee_share'])->toBe('1386.00');
        expect($result['employer_share'])->toBe('2362.50');
    });
});

describe('personalExemption', function (): void {

    it('returns 12000 annual exemption', function (): void {
        expect(EgyptianTaxService::personalExemption())->toBe('12000.00');
    });
});
