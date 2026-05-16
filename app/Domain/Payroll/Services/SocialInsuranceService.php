<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Services;

use App\Domain\Payroll\Models\Employee;
use App\Domain\Payroll\Models\EmployeeInsuranceRecord;
use App\Domain\Payroll\Models\SocialInsuranceRate;

class SocialInsuranceService
{
    /**
     * Calculate social insurance for an employee for a given month.
     *
     * @return array{employee_basic: string, employee_variable: string, employee_total: string, employer_basic: string, employer_variable: string, employer_total: string, basic_insurance_salary: string, variable_insurance_salary: string}
     */
    public function calculate(int $employeeId, string $month): array
    {
        $record = EmployeeInsuranceRecord::where('employee_id', $employeeId)
            ->where('is_active', true)
            ->firstOrFail();

        // Parse year from month (YYYY-MM format)
        $year = (int) substr($month, 0, 4);
        $rates = $this->getRates($year);

        $basicSalary = $record->basic_insurance_salary;
        $variableSalary = $record->variable_insurance_salary;

        // Cap salaries
        $cappedBasic = bccomp($basicSalary, $rates['basic_max_salary'], 2) <= 0
            ? $basicSalary
            : $rates['basic_max_salary'];

        $cappedVariable = bccomp($variableSalary, $rates['variable_max_salary'], 2) <= 0
            ? $variableSalary
            : $rates['variable_max_salary'];

        // Employee shares
        $employeeBasic = bcmul($cappedBasic, $rates['basic_employee_rate'], 2);
        $employeeVariable = bcmul($cappedVariable, $rates['variable_employee_rate'], 2);
        $employeeTotal = bcadd($employeeBasic, $employeeVariable, 2);

        // Employer shares
        $employerBasic = bcmul($cappedBasic, $rates['basic_employer_rate'], 2);
        $employerVariable = bcmul($cappedVariable, $rates['variable_employer_rate'], 2);
        $employerTotal = bcadd($employerBasic, $employerVariable, 2);

        return [
            'employee_basic' => $employeeBasic,
            'employee_variable' => $employeeVariable,
            'employee_total' => $employeeTotal,
            'employer_basic' => $employerBasic,
            'employer_variable' => $employerVariable,
            'employer_total' => $employerTotal,
            'basic_insurance_salary' => $cappedBasic,
            'variable_insurance_salary' => $cappedVariable,
        ];
    }

    /**
     * Generate SISA monthly report for all active insured employees.
     *
     * @return array{employees: array<int, array>, totals: array{employee_share: string, employer_share: string, total: string}}
     */
    public function monthlyReport(string $month): array
    {
        // Eager-load the nested employee.user relation — the report reads
        // $record->employee->user->name and strict mode prevents lazy loads.
        $records = EmployeeInsuranceRecord::where('is_active', true)
            ->where('insurance_type', '!=', 'exempted')
            ->with('employee.user')
            ->get();

        $employees = [];
        $totalEmployeeShare = '0.00';
        $totalEmployerShare = '0.00';

        foreach ($records as $record) {
            $breakdown = $this->calculate($record->employee_id, $month);

            $employees[] = [
                'employee_id' => $record->employee_id,
                'insurance_number' => $record->insurance_number,
                'name' => $record->employee?->user?->name ?? '',
                'basic_insurance_salary' => $breakdown['basic_insurance_salary'],
                'variable_insurance_salary' => $breakdown['variable_insurance_salary'],
                'employee_share' => $breakdown['employee_total'],
                'employer_share' => $breakdown['employer_total'],
            ];

            $totalEmployeeShare = bcadd($totalEmployeeShare, $breakdown['employee_total'], 2);
            $totalEmployerShare = bcadd($totalEmployerShare, $breakdown['employer_total'], 2);
        }

        $total = bcadd($totalEmployeeShare, $totalEmployerShare, 2);

        return [
            'employees' => $employees,
            'totals' => [
                'employee_share' => $totalEmployeeShare,
                'employer_share' => $totalEmployerShare,
                'total' => $total,
            ],
        ];
    }

    /**
     * Create or update an employee's insurance record.
     */
    public function registerEmployee(int $employeeId, array $data): EmployeeInsuranceRecord
    {
        $employee = Employee::findOrFail($employeeId);

        return EmployeeInsuranceRecord::updateOrCreate(
            [
                'tenant_id' => $employee->tenant_id,
                'employee_id' => $employeeId,
            ],
            [
                'insurance_number' => $data['insurance_number'] ?? null,
                'registration_date' => $data['registration_date'] ?? now()->toDateString(),
                'insurance_type' => $data['insurance_type'] ?? 'regular',
                'basic_insurance_salary' => $data['basic_insurance_salary'],
                'variable_insurance_salary' => $data['variable_insurance_salary'],
                'is_active' => $data['is_active'] ?? true,
                'notes' => $data['notes'] ?? null,
            ],
        );
    }

    /**
     * Get rates for a given year, seeding 2025 defaults if none exist.
     *
     * @return array{basic_employee_rate: string, basic_employer_rate: string, variable_employee_rate: string, variable_employer_rate: string, basic_max_salary: string, variable_max_salary: string, minimum_subscription: string}
     */
    public function getRates(int $year): array
    {
        $rate = SocialInsuranceRate::where('year', $year)->first();

        if (! $rate) {
            // Seed 2025 defaults if requesting any year without rates
            $rate = SocialInsuranceRate::create([
                'year' => $year,
                'basic_employee_rate' => '0.1100',
                'basic_employer_rate' => '0.1875',
                'variable_employee_rate' => '0.1100',
                'variable_employer_rate' => '0.1875',
                'basic_max_salary' => '12600.00',
                'variable_max_salary' => '10500.00',
                'minimum_subscription' => '2100.00',
                'effective_from' => $year.'-01-01',
            ]);
        }

        return [
            'basic_employee_rate' => $rate->basic_employee_rate,
            'basic_employer_rate' => $rate->basic_employer_rate,
            'variable_employee_rate' => $rate->variable_employee_rate,
            'variable_employer_rate' => $rate->variable_employer_rate,
            'basic_max_salary' => $rate->basic_max_salary,
            'variable_max_salary' => $rate->variable_max_salary,
            'minimum_subscription' => $rate->minimum_subscription,
        ];
    }

    /**
     * Check if an employee qualifies for social insurance exemptions.
     *
     * @return array{is_exempt: bool, reasons: array<int, string>}
     */
    public function exemptionCheck(int $employeeId): array
    {
        $employee = Employee::with('user')->findOrFail($employeeId);
        $record = EmployeeInsuranceRecord::where('employee_id', $employeeId)->first();

        $reasons = [];
        $isExempt = false;

        // Check insurance type
        if ($record && $record->insurance_type === 'exempted') {
            $isExempt = true;
            $reasons[] = 'Employee is marked as exempted';
        }

        // Check foreigner status
        if ($record && $record->insurance_type === 'foreigner') {
            $isExempt = true;
            $reasons[] = 'Foreign employee — subject to bilateral agreements';
        }

        // Check age — employees over 60 may have different rules
        if ($employee->user && $employee->user->date_of_birth) {
            $age = $employee->user->date_of_birth->age;
            if ($age >= 60) {
                $isExempt = true;
                $reasons[] = 'Employee has reached retirement age (60+)';
            }
        }

        // Check if not insured
        if (! $employee->is_insured) {
            $isExempt = true;
            $reasons[] = 'Employee is not registered for insurance';
        }

        return [
            'is_exempt' => $isExempt,
            'reasons' => $reasons,
        ];
    }
}
