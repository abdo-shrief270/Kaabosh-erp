<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Payroll\Enums\CalculationType;
use App\Domain\Payroll\Enums\SalaryComponentType;
use App\Domain\Payroll\Models\SalaryComponent;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SalaryComponent> */
class SalaryComponentFactory extends Factory
{
    protected $model = SalaryComponent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name_ar' => fake()->randomElement(['بدل مواصلات', 'بدل سكن', 'بدل طعام', 'خصم تأخير']),
            'name_en' => fake()->randomElement(['Transport Allowance', 'Housing Allowance', 'Meal Allowance', 'Late Deduction']),
            'code' => fake()->unique()->regexify('[A-Z]{3}[0-9]{3}'),
            'type' => fake()->randomElement(SalaryComponentType::cases()),
            'calculation_type' => fake()->randomElement(CalculationType::cases()),
            'default_amount' => fake()->randomFloat(2, 100, 5000),
            'is_taxable' => fake()->boolean(70),
            'is_active' => true,
        ];
    }
}
