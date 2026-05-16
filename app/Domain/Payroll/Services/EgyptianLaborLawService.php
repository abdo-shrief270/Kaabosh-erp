<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Services;

use App\Domain\Payroll\Enums\OvertimeType;

class EgyptianLaborLawService
{
    // Egyptian minimum wage (EGP/month) as of 2025
    private const MINIMUM_WAGE = '6000.00';

    // Social insurance rates
    private const SI_EMPLOYEE_RATE = '0.11';

    private const SI_EMPLOYER_RATE = '0.1875';

    private const SI_MAX_BASIC_SALARY = '12600.00';

    private const SI_MAX_VARIABLE_SALARY = '10500.00';

    /**
     * Calculate overtime pay based on Egyptian labor law.
     *
     * Daily rate = baseSalary / 30, hourly = daily / 8.
     * Overtime = hours * hourly * overtimeRate.
     */
    public static function calculateOvertime(string $baseSalary, string $hoursWorked, string $overtimeType): string
    {
        $rate = OvertimeType::from($overtimeType)->rate();

        $dailyRate = bcdiv($baseSalary, '30', 6);
        $hourlyRate = bcdiv($dailyRate, '8', 6);

        return bcmul(bcmul($hoursWorked, $hourlyRate, 6), $rate, 2);
    }

    /**
     * Calculate end-of-service gratuity per Egyptian labor law.
     *
     * Terminated: 2 months salary per year (first 5 years), 2.5 months per year after.
     * Resigned: 0.5 months per year (first 5), 1 month per year (5-10), 1.5 months per year (10+).
     *
     * @return array{entitled_months: string, amount: string, breakdown: array<int, array{years: int, rate: string, months: string}>}
     */
    public static function calculateEndOfService(string $monthlySalary, int $yearsOfService, string $terminationType): array
    {
        $breakdown = [];
        $totalMonths = '0.00';

        if ($terminationType === 'resigned') {
            // Resignation rules
            $firstFive = min($yearsOfService, 5);
            $nextFive = min(max($yearsOfService - 5, 0), 5);
            $remaining = max($yearsOfService - 10, 0);

            if ($firstFive > 0) {
                $months = bcmul((string) $firstFive, '0.50', 2);
                $totalMonths = bcadd($totalMonths, $months, 2);
                $breakdown[] = ['years' => $firstFive, 'rate' => '0.50', 'months' => $months];
            }

            if ($nextFive > 0) {
                $months = bcmul((string) $nextFive, '1.00', 2);
                $totalMonths = bcadd($totalMonths, $months, 2);
                $breakdown[] = ['years' => $nextFive, 'rate' => '1.00', 'months' => $months];
            }

            if ($remaining > 0) {
                $months = bcmul((string) $remaining, '1.50', 2);
                $totalMonths = bcadd($totalMonths, $months, 2);
                $breakdown[] = ['years' => $remaining, 'rate' => '1.50', 'months' => $months];
            }
        } else {
            // Terminated / end_of_contract / retired
            $firstFive = min($yearsOfService, 5);
            $remaining = max($yearsOfService - 5, 0);

            if ($firstFive > 0) {
                $months = bcmul((string) $firstFive, '2.00', 2);
                $totalMonths = bcadd($totalMonths, $months, 2);
                $breakdown[] = ['years' => $firstFive, 'rate' => '2.00', 'months' => $months];
            }

            if ($remaining > 0) {
                $months = bcmul((string) $remaining, '2.50', 2);
                $totalMonths = bcadd($totalMonths, $months, 2);
                $breakdown[] = ['years' => $remaining, 'rate' => '2.50', 'months' => $months];
            }
        }

        $amount = bcmul($monthlySalary, $totalMonths, 2);

        return [
            'entitled_months' => $totalMonths,
            'amount' => $amount,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Annual leave entitlement per Egyptian labor law.
     *
     * < 10 years service: 21 days, >= 10 years: 30 days, age >= 50: 45 days.
     * Note: age-based check (45 days) should be handled by caller; this returns
     * the service-based entitlement only.
     */
    public static function calculateAnnualLeaveEntitlement(int $yearsOfService): int
    {
        if ($yearsOfService >= 10) {
            return 30;
        }

        return 21;
    }

    /**
     * Sick leave entitlement per Egyptian labor law (per 3-year cycle).
     *
     * @return array<int, array{period: string, pay_rate: string}>
     */
    public static function calculateSickLeaveEntitlement(): array
    {
        return [
            ['period' => '3 months', 'pay_rate' => '1.00'],
            ['period' => '6 months', 'pay_rate' => '0.85'],
            ['period' => '3 months', 'pay_rate' => '0.75'],
        ];
    }

    /**
     * Maternity leave entitlement per Egyptian labor law.
     *
     * @return array{days: int, pay_rate: string, max_occurrences: int}
     */
    public static function calculateMaternityLeave(): array
    {
        return [
            'days' => 90,
            'pay_rate' => '1.00',
            'max_occurrences' => 3,
        ];
    }

    /**
     * Validate salary meets Egyptian minimum wage.
     */
    public static function validateMinimumWage(string $salary): bool
    {
        return bccomp($salary, self::MINIMUM_WAGE, 2) >= 0;
    }

    /**
     * Calculate social insurance with basic + variable salary components.
     *
     * Employee: 11% of basic (max 12,600) + 11% of variable (max 10,500).
     * Employer: 18.75% of basic + 18.75% of variable.
     *
     * @return array{employee_basic: string, employee_variable: string, employee_total: string, employer_basic: string, employer_variable: string, employer_total: string}
     */
    public static function calculateSocialInsurance(string $basicSalary, string $variableSalary): array
    {
        $cappedBasic = bccomp($basicSalary, self::SI_MAX_BASIC_SALARY, 2) <= 0
            ? $basicSalary
            : self::SI_MAX_BASIC_SALARY;

        $cappedVariable = bccomp($variableSalary, self::SI_MAX_VARIABLE_SALARY, 2) <= 0
            ? $variableSalary
            : self::SI_MAX_VARIABLE_SALARY;

        $employeeBasic = bcmul($cappedBasic, self::SI_EMPLOYEE_RATE, 2);
        $employeeVariable = bcmul($cappedVariable, self::SI_EMPLOYEE_RATE, 2);
        $employeeTotal = bcadd($employeeBasic, $employeeVariable, 2);

        $employerBasic = bcmul($cappedBasic, self::SI_EMPLOYER_RATE, 2);
        $employerVariable = bcmul($cappedVariable, self::SI_EMPLOYER_RATE, 2);
        $employerTotal = bcadd($employerBasic, $employerVariable, 2);

        return [
            'employee_basic' => $employeeBasic,
            'employee_variable' => $employeeVariable,
            'employee_total' => $employeeTotal,
            'employer_basic' => $employerBasic,
            'employer_variable' => $employerVariable,
            'employer_total' => $employerTotal,
        ];
    }
}
