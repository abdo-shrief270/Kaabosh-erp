<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Accounting\Models\FiscalYear;
use App\Domain\Tax\Enums\TaxReturnStatus;
use App\Domain\Tax\Enums\TaxReturnType;
use App\Domain\Tax\Models\TaxReturn;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TaxReturn> */
class TaxReturnFactory extends Factory
{
    protected $model = TaxReturn::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $from = fake()->dateTimeBetween('-12 months', '-1 month');

        return [
            'tenant_id' => Tenant::factory(),
            'fiscal_year_id' => FiscalYear::factory(),
            'type' => fake()->randomElement(TaxReturnType::cases()),
            'status' => TaxReturnStatus::Draft,
            'period_from' => $from,
            'period_to' => fake()->dateTimeBetween($from, 'now'),
            'revenue' => '0.00',
            'expenses' => '0.00',
            'taxable_income' => '0.00',
            'tax_amount' => '0.00',
            'output_vat' => '0.00',
            'input_vat' => '0.00',
            'net_vat' => '0.00',
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function corporate(): static
    {
        return $this->state([
            'type' => TaxReturnType::CorporateTax,
        ]);
    }

    public function vat(): static
    {
        return $this->state([
            'type' => TaxReturnType::Vat,
        ]);
    }

    public function calculated(): static
    {
        return $this->state([
            'status' => TaxReturnStatus::Calculated,
        ]);
    }

    public function filed(): static
    {
        return $this->state([
            'status' => TaxReturnStatus::Filed,
            'filed_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->state([
            'status' => TaxReturnStatus::Paid,
            'filed_at' => now()->subDays(7),
            'paid_at' => now(),
            'payment_reference' => 'PAY-'.fake()->numerify('######'),
        ]);
    }
}
