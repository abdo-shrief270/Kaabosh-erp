<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Expenses\Enums\ExpensePaymentMethod;
use App\Domain\Expenses\Enums\ExpenseStatus;
use App\Domain\Expenses\Models\Expense;
use App\Domain\Expenses\Models\ExpenseCategory;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Expense> */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'expense_category_id' => ExpenseCategory::factory(),
            'user_id' => User::factory(),
            'amount' => fake()->randomFloat(2, 10, 5000),
            'currency' => 'EGP',
            'date' => fake()->dateTimeBetween('-3 months', 'now'),
            'description' => fake()->sentence(),
            'status' => ExpenseStatus::Draft,
            'payment_method' => fake()->randomElement(ExpensePaymentMethod::cases()),
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn () => [
            'status' => ExpenseStatus::Submitted,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => ExpenseStatus::Approved,
            'approved_at' => now(),
            'approved_by' => User::factory(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => ExpenseStatus::Rejected,
        ]);
    }

    public function reimbursed(): static
    {
        return $this->state(fn () => [
            'status' => ExpenseStatus::Reimbursed,
            'approved_at' => now()->subDay(),
            'approved_by' => User::factory(),
            'reimbursed_at' => now(),
        ]);
    }
}
