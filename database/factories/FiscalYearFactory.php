<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Accounting\Models\FiscalYear;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FiscalYear> */
class FiscalYearFactory extends Factory
{
    protected $model = FiscalYear::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $year = $this->faker->unique()->numberBetween(2020, 2035);

        return [
            'tenant_id' => Tenant::factory(),
            'name' => (string) $year,
            'start_date' => "{$year}-01-01",
            'end_date' => "{$year}-12-31",
            'is_closed' => false,
            'closed_at' => null,
            'closed_by' => null,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'is_closed' => true,
            'closed_at' => now(),
        ]);
    }
}
