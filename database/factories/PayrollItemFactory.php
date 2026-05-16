<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Payroll\Models\Employee;
use App\Domain\Payroll\Models\PayrollItem;
use App\Domain\Payroll\Models\PayrollRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PayrollItem> */
class PayrollItemFactory extends Factory
{
    protected $model = PayrollItem::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $baseSalary = fake()->randomFloat(2, 3000, 30000);
        $grossSalary = $baseSalary;
        $siEmployee = round($baseSalary * 0.11, 2);
        $netSalary = round($grossSalary - $siEmployee, 2);

        return [
            'payroll_run_id' => PayrollRun::factory(),
            'employee_id' => Employee::factory(),
            'base_salary' => $baseSalary,
            'allowances' => 0,
            'overtime_hours' => 0,
            'overtime_amount' => 0,
            'gross_salary' => $grossSalary,
            'social_insurance_employee' => $siEmployee,
            'social_insurance_employer' => round($baseSalary * 0.1875, 2),
            'taxable_income' => max(0, $grossSalary - $siEmployee - 1000),
            'income_tax' => 0,
            'other_deductions' => 0,
            'net_salary' => $netSalary,
        ];
    }
}
