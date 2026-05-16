<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Subscription\Enums\PaymentGateway;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Plan;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Subscription> */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'plan_id' => Plan::factory(),
            'status' => SubscriptionStatus::Trial,
            'billing_cycle' => 'monthly',
            'price' => 0,
            'currency' => 'EGP',
            'trial_ends_at' => now()->addDays(14),
            'current_period_start' => today(),
            'current_period_end' => today()->addDays(14),
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'expires_at' => null,
            'gateway' => null,
            'gateway_subscription_id' => null,
            'metadata' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::Active,
            'price' => fake()->randomFloat(2, 99, 1499),
            'trial_ends_at' => now()->subDays(14),
            'current_period_start' => today(),
            'current_period_end' => today()->addMonth(),
            'gateway' => PaymentGateway::Paymob,
        ]);
    }

    public function pastDue(): static
    {
        return $this->state(fn () => [
            'status' => SubscriptionStatus::PastDue,
            'price' => fake()->randomFloat(2, 99, 1499),
            'trial_ends_at' => now()->subDays(30),
            'current_period_start' => today()->subMonth(),
            'current_period_end' => today()->subDays(3),
            'gateway' => PaymentGateway::Paymob,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => SubscriptionStatus::Cancelled,
            'cancelled_at' => now()->subDays(5),
            'cancellation_reason' => fake()->optional(0.7)->randomElement([
                'Too expensive',
                'Switching to another provider',
                'No longer needed',
            ]),
            'current_period_start' => today()->subMonth(),
            'current_period_end' => today()->addDays(10),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => SubscriptionStatus::Expired,
            'trial_ends_at' => now()->subDays(30),
            'current_period_start' => today()->subMonths(2),
            'current_period_end' => today()->subMonth(),
            'expires_at' => now()->subDays(7),
        ]);
    }

    public function annual(): static
    {
        return $this->state(fn () => [
            'billing_cycle' => 'annual',
            'current_period_start' => today(),
            'current_period_end' => today()->addYear(),
        ]);
    }
}
