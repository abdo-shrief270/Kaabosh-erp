<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Accounting\Models\FiscalYear;
use App\Domain\Tax\Enums\TaxAdjustmentType;
use App\Domain\Tax\Models\TaxAdjustment;
use App\Domain\Tax\Models\TaxReturn;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TaxAdjustment> */
class TaxAdjustmentFactory extends Factory
{
    protected $model = TaxAdjustment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'fiscal_year_id' => FiscalYear::factory(),
            'tax_return_id' => TaxReturn::factory(),
            'type' => fake()->randomElement(TaxAdjustmentType::cases()),
            'description' => fake()->sentence(),
            'amount' => fake()->randomFloat(2, 1000, 100000),
            'is_addition' => fake()->boolean(),
        ];
    }

    public function addition(): static
    {
        return $this->state([
            'is_addition' => true,
        ]);
    }

    public function deduction(): static
    {
        return $this->state([
            'is_addition' => false,
        ]);
    }

    public function nonDeductible(): static
    {
        return $this->state([
            'type' => TaxAdjustmentType::NonDeductibleExpense,
            'is_addition' => true,
        ]);
    }

    public function lossCarryforward(): static
    {
        return $this->state([
            'type' => TaxAdjustmentType::LossCarryforward,
            'is_addition' => false,
        ]);
    }
}
