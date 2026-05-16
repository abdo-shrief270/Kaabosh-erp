<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Accounting\Models\FiscalPeriod;
use App\Domain\Accounting\Models\FiscalYear;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FiscalPeriod> */
class FiscalPeriodFactory extends Factory
{
    protected $model = FiscalPeriod::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $month = $this->faker->unique()->numberBetween(1, 12);
        $monthName = date('F', mktime(0, 0, 0, $month, 1));
        $year = 2026;
        $lastDay = date('t', mktime(0, 0, 0, $month, 1, $year));

        return [
            'tenant_id' => Tenant::factory(),
            'fiscal_year_id' => FiscalYear::factory(),
            'name' => "{$monthName} {$year}",
            'period_number' => $month,
            'start_date' => sprintf('%d-%02d-01', $year, $month),
            'end_date' => sprintf('%d-%02d-%02d', $year, $month, $lastDay),
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
