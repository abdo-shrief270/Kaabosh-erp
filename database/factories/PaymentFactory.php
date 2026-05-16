<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Billing\Enums\PaymentMethod;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\Payment;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'invoice_id' => Invoice::factory(),
            'amount' => fake()->randomFloat(2, 100, 10000),
            'date' => today(),
            'method' => PaymentMethod::Cash,
            'reference' => null,
            'notes' => null,
            'journal_entry_id' => null,
            'created_by' => null,
        ];
    }

    public function bankTransfer(): static
    {
        return $this->state(fn () => [
            'method' => PaymentMethod::BankTransfer,
            'reference' => fake()->bothify('TRF-########'),
        ]);
    }

    public function check(): static
    {
        return $this->state(fn () => [
            'method' => PaymentMethod::Check,
            'reference' => fake()->bothify('CHK-######'),
        ]);
    }

    public function withReference(): static
    {
        return $this->state(fn () => [
            'reference' => fake()->bothify('REF-########'),
        ]);
    }
}
