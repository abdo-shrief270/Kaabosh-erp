<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Services;

class EgyptianTaxService
{
    // 2025 Egyptian social insurance rates
    private const SI_EMPLOYEE_RATE = '0.11';

    private const SI_EMPLOYER_RATE = '0.1875';

    private const SI_MAX_BASIC_SALARY = '12600.00';

    // Annual personal exemption
    private const PERSONAL_EXEMPTION = '12000.00';

    /**
     * Calculate social insurance shares.
     *
     * @return array{employee_share: string, employer_share: string}
     */
    public static function calculateSocialInsurance(string $baseSalary, bool $isInsured): array
    {
        if (! $isInsured) {
            return ['employee_share' => '0.00', 'employer_share' => '0.00'];
        }

        $cappedSalary = bccomp($baseSalary, self::SI_MAX_BASIC_SALARY, 2) <= 0
            ? $baseSalary
            : self::SI_MAX_BASIC_SALARY;

        return [
            'employee_share' => bcmul($cappedSalary, self::SI_EMPLOYEE_RATE, 2),
            'employer_share' => bcmul($cappedSalary, self::SI_EMPLOYER_RATE, 2),
        ];
    }

    /**
     * Calculate annual income tax using Egyptian progressive brackets.
     *
     * Brackets (2023+):
     *   0       – 40,000  => 0%
     *   40,001  – 55,000  => 10%
     *   55,001  – 70,000  => 15%
     *   70,001  – 200,000 => 20%
     *   200,001 – 400,000 => 22.5%
     *   400,001 – 600,000 => 25%
     *   600,001+          => 27.5%
     */
    public static function calculateIncomeTax(string $annualTaxableIncome): string
    {
        if (bccomp($annualTaxableIncome, '0', 2) <= 0) {
            return '0.00';
        }

        $brackets = [
            ['40000.00', '0.00'],
            ['15000.00', '0.10'],   // 40,001 – 55,000
            ['15000.00', '0.15'],   // 55,001 – 70,000
            ['130000.00', '0.20'],  // 70,001 – 200,000
            ['200000.00', '0.225'], // 200,001 – 400,000
            ['200000.00', '0.25'],  // 400,001 – 600,000
            ['999999999999.00', '0.275'], // 600,001+
        ];

        $tax = '0.00';
        $remaining = $annualTaxableIncome;

        foreach ($brackets as [$width, $rate]) {
            if (bccomp($remaining, '0', 2) <= 0) {
                break;
            }

            $taxable = bccomp($remaining, $width, 2) <= 0 ? $remaining : $width;
            $tax = bcadd($tax, bcmul($taxable, $rate, 2), 2);
            $remaining = bcsub($remaining, $taxable, 2);
        }

        return $tax;
    }

    /**
     * Annual personal exemption per Egyptian law.
     */
    public static function personalExemption(): string
    {
        return self::PERSONAL_EXEMPTION;
    }
}
