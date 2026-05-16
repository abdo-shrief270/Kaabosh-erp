<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Investor\Enums\DistributionStatus;
use App\Domain\Investor\Models\Investor;
use App\Domain\Investor\Models\ProfitDistribution;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProfitDistribution> */
class ProfitDistributionFactory extends Factory
{
    protected $model = ProfitDistribution::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $revenue = fake()->randomFloat(2, 5000, 50000);
        $expenses = fake()->randomFloat(2, 1000, 20000);
        $net = round($revenue - $expenses, 2);
        $percentage = fake()->randomFloat(2, 5, 50);
        $share = round(max(0, $net) * $percentage / 100, 2);

        return [
            'investor_id' => Investor::factory(),
            'tenant_id' => Tenant::factory(),
            'month' => (int) now()->format('m'),
            'year' => (int) now()->format('Y'),
            'tenant_revenue' => $revenue,
            'tenant_expenses' => $expenses,
            'net_profit' => $net,
            'ownership_percentage' => $percentage,
            'investor_share' => $share,
            'status' => DistributionStatus::Draft,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => DistributionStatus::Approved]);
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => DistributionStatus::Paid,
            'paid_at' => now(),
        ]);
    }
}
