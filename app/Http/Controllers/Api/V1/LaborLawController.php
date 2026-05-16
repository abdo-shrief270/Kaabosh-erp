<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Payroll\Enums\OvertimeType;
use App\Domain\Payroll\Models\Employee;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LaborLawController extends Controller
{
    /**
     * Egyptian Labor Law minimum wage (effective 2024).
     */
    private const string MINIMUM_WAGE = '6000.00';

    /**
     * Social insurance caps per Egyptian Social Insurance Law No. 148/2019.
     */
    private const string BASIC_SALARY_CAP = '12600.00';

    private const string VARIABLE_SALARY_CAP = '10800.00';

    /**
     * Social insurance contribution rates.
     */
    private const float EMPLOYER_BASIC_RATE = 0.18;

    private const float EMPLOYER_VARIABLE_RATE = 0.18;

    private const float EMPLOYEE_BASIC_RATE = 0.11;

    private const float EMPLOYEE_VARIABLE_RATE = 0.11;

    /**
     * Calculate overtime pay per Egyptian Labor Law Articles 85-86.
     *
     * Formula: (base_salary / 30 / 8) * hours * overtime_type_rate
     */
    public function calculateOvertime(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'base_salary' => ['required', 'numeric', 'min:0'],
            'hours' => ['required', 'numeric', 'min:0'],
            'overtime_type' => ['required', 'string', 'in:weekday,friday'],
        ]);

        $overtimeType = OvertimeType::from($validated['overtime_type']);
        $hourlyRate = (float) $validated['base_salary'] / 30 / 8;
        $amount = $hourlyRate * (float) $validated['hours'] * $overtimeType->rate();

        return $this->success([
            'base_salary' => number_format((float) $validated['base_salary'], 2, '.', ''),
            'hours' => (float) $validated['hours'],
            'overtime_type' => $overtimeType->value,
            'rate' => $overtimeType->rate(),
            'hourly_rate' => number_format($hourlyRate, 2, '.', ''),
            'amount' => number_format($amount, 2, '.', ''),
        ]);
    }

    /**
     * Calculate end-of-service gratuity per Egyptian Labor Law Article 126.
     *
     * - First 5 years: 2 months salary per year
     * - After 5 years: 2.5 months salary per year
     * - termination_type: 'resignation' or 'termination' (both same calc in Egypt)
     */
    public function calculateEndOfService(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'monthly_salary' => ['required', 'numeric', 'min:0'],
            'years_of_service' => ['required', 'numeric', 'min:0'],
            'termination_type' => ['required', 'string', 'in:resignation,termination'],
        ]);

        $salary = (float) $validated['monthly_salary'];
        $years = (float) $validated['years_of_service'];

        $firstFiveYears = min($years, 5);
        $remainingYears = max($years - 5, 0);

        $totalMonths = ($firstFiveYears * 2) + ($remainingYears * 2.5);
        $amount = $salary * $totalMonths;

        return $this->success([
            'monthly_salary' => number_format($salary, 2, '.', ''),
            'years_of_service' => $years,
            'termination_type' => $validated['termination_type'],
            'total_months' => $totalMonths,
            'amount' => number_format($amount, 2, '.', ''),
        ]);
    }

    /**
     * Return leave entitlements per Egyptian Labor Law Articles 47-54.
     *
     * Annual leave:
     *   - < 10 years service: 21 days
     *   - >= 10 years service (or age >= 50): 30 days
     * Sick leave: 180 days per year (with varying pay rates)
     * Maternity leave: 90 days
     */
    public function leaveEntitlement(Employee $employee): JsonResponse
    {
        $yearsOfService = (int) $employee->hire_date->diffInYears(now());

        $annualLeaveDays = $yearsOfService >= 10 ? 30 : 21;

        return $this->success([
            'employee_id' => $employee->id,
            'years_of_service' => $yearsOfService,
            'annual_leave_days' => $annualLeaveDays,
            'sick_leave_days' => 180,
            'maternity_leave_days' => 90,
        ]);
    }

    /**
     * Validate salary against Egyptian minimum wage.
     */
    public function validateWage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'salary' => ['required', 'numeric', 'min:0'],
        ]);

        $salary = (float) $validated['salary'];
        $minimum = (float) self::MINIMUM_WAGE;

        return $this->success([
            'valid' => $salary >= $minimum,
            'minimum' => self::MINIMUM_WAGE,
            'salary' => number_format($salary, 2, '.', ''),
        ]);
    }

    /**
     * Calculate social insurance contributions per Law 148/2019.
     *
     * Employer: 18% basic + 18% variable
     * Employee: 11% basic + 11% variable
     * Caps: basic 12,600 EGP, variable 10,800 EGP
     */
    public function socialInsurance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'variable_salary' => ['required', 'numeric', 'min:0'],
        ]);

        $basicSalary = min((float) $validated['basic_salary'], (float) self::BASIC_SALARY_CAP);
        $variableSalary = min((float) $validated['variable_salary'], (float) self::VARIABLE_SALARY_CAP);

        $employerBasic = $basicSalary * self::EMPLOYER_BASIC_RATE;
        $employerVariable = $variableSalary * self::EMPLOYER_VARIABLE_RATE;
        $employeeBasic = $basicSalary * self::EMPLOYEE_BASIC_RATE;
        $employeeVariable = $variableSalary * self::EMPLOYEE_VARIABLE_RATE;

        return $this->success([
            'basic_salary' => number_format($basicSalary, 2, '.', ''),
            'variable_salary' => number_format($variableSalary, 2, '.', ''),
            'employer_basic' => number_format($employerBasic, 2, '.', ''),
            'employer_variable' => number_format($employerVariable, 2, '.', ''),
            'employer_total' => number_format($employerBasic + $employerVariable, 2, '.', ''),
            'employee_basic' => number_format($employeeBasic, 2, '.', ''),
            'employee_variable' => number_format($employeeVariable, 2, '.', ''),
            'employee_total' => number_format($employeeBasic + $employeeVariable, 2, '.', ''),
        ]);
    }
}
