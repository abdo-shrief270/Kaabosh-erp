<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Subscription\Enums\PaymentGateway;
use App\Domain\Subscription\Enums\PaymentStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionPayment;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SubscriptionPayment> */
class SubscriptionPaymentFactory extends Factory
{
    protected $model = SubscriptionPayment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'subscription_id' => Subscription::factory(),
            'amount' => fake()->randomFloat(2, 99, 1499),
            'currency' => 'EGP',
            'status' => PaymentStatus::Pending,
            'gateway' => PaymentGateway::Paymob,
            'gateway_transaction_id' => null,
            'gateway_order_id' => null,
            'payment_method_type' => null,
            'billing_period_start' => today(),
            'billing_period_end' => today()->addMonth(),
            'paid_at' => null,
            'failed_at' => null,
            'failure_reason' => null,
            'refunded_at' => null,
            'receipt_url' => null,
            'metadata' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Completed,
            'gateway_transaction_id' => 'txn_'.fake()->unique()->numerify('##########'),
            'gateway_order_id' => 'ord_'.fake()->unique()->numerify('########'),
            'payment_method_type' => fake()->randomElement(['card', 'wallet', 'bank_transfer']),
            'paid_at' => now(),
            'receipt_url' => fake()->optional(0.5)->url(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Failed,
            'failed_at' => now(),
            'failure_reason' => fake()->randomElement([
                'Insufficient funds',
                'Card declined',
                'Payment timeout',
                'Gateway error',
            ]),
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Refunded,
            'gateway_transaction_id' => 'txn_'.fake()->unique()->numerify('##########'),
            'paid_at' => now()->subDays(7),
            'refunded_at' => now(),
        ]);
    }
}
