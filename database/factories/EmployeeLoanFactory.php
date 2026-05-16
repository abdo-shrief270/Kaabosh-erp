<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Payroll\Enums\LoanStatus;
use App\Domain\Payroll\Enums\LoanType;
use App\Domain\Payroll\Models\Employee;
use App\Domain\Payroll\Models\EmployeeLoan;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmployeeLoan> */
class EmployeeLoanFactory extends Factory
{
    protected $model = EmployeeLoan::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 5000, 50000);

        return [
            'tenant_id' => Tenant::factory(),
            'employee_id' => Employee::factory(),
            'loan_type' => fake()->randomElement(LoanType::cases()),
            'amount' => $amount,
            'installment_amount' => round($amount / fake()->numberBetween(6, 24), 2),
            'remaining_balance' => $amount,
            'start_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'status' => LoanStatus::Active,
        ];
    }
}
