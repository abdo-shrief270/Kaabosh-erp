<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\AccountsPayable\Models\Bill;
use App\Domain\AccountsPayable\Models\Vendor;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Bill> */
class BillFactory extends Factory
{
    protected $model = Bill::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $date = fake()->dateTimeBetween('-3 months', 'now');

        return [
            'tenant_id' => Tenant::factory(),
            'vendor_id' => Vendor::factory(),
            'bill_number' => 'BILL-'.fake()->unique()->numerify('######'),
            'vendor_invoice_number' => fake()->optional()->numerify('INV-####'),
            'type' => 'bill',
            'status' => 'draft',
            'date' => $date,
            'due_date' => fake()->dateTimeBetween($date, '+60 days'),
            'currency' => 'EGP',
            'subtotal' => '0.00',
            'discount_amount' => '0.00',
            'vat_amount' => '0.00',
            'wht_amount' => '0.00',
            'total' => '0.00',
            'amount_paid' => '0.00',
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function approved(): static
    {
        return $this->state([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'approved_at' => now()->subDays(7),
            'amount_paid' => $attributes['total'],
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }
}
