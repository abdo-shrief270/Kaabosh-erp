<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Payroll\Models\Employee;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Employee> */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'hire_date' => fake()->dateTimeBetween('-2 years', 'now'),
            'department' => fake()->randomElement(['محاسبة', 'مراجعة', 'ضرائب', 'إدارة']),
            'job_title' => fake()->randomElement(['محاسب', 'مراجع', 'مدير', 'محلل مالي']),
            'base_salary' => fake()->randomFloat(2, 3000, 30000),
            'is_insured' => fake()->boolean(70),
        ];
    }

    public function insured(): static
    {
        return $this->state(fn () => ['is_insured' => true]);
    }

    public function uninsured(): static
    {
        return $this->state(fn () => ['is_insured' => false]);
    }
}
