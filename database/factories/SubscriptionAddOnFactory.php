<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Subscription\Enums\AddOnBillingCycle;
use App\Domain\Subscription\Enums\SubscriptionAddOnStatus;
use App\Domain\Subscription\Models\AddOn;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionAddOn;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SubscriptionAddOn> */
class SubscriptionAddOnFactory extends Factory
{
    protected $model = SubscriptionAddOn::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => fn (array $attrs) => Subscription::query()->find($attrs['subscription_id'])?->tenant_id ?? 1,
            'subscription_id' => Subscription::factory(),
            'add_on_id' => AddOn::factory(),
            'quantity' => 1,
            'status' => SubscriptionAddOnStatus::Active->value,
            'billing_cycle' => AddOnBillingCycle::Monthly->value,
            'price' => fake()->randomFloat(2, 49, 299),
            'currency' => 'EGP',
            'current_period_start' => now()->startOfMonth()->toDateString(),
            'current_period_end' => now()->endOfMonth()->toDateString(),
            'cancel_at_period_end' => false,
        ];
    }

    public function cancelled(): self
    {
        return $this->state(fn () => [
            'status' => SubscriptionAddOnStatus::Cancelled->value,
            'cancelled_at' => now(),
            'expires_at' => now(),
        ]);
    }

    public function expiringSoon(): self
    {
        return $this->state(fn () => [
            'cancel_at_period_end' => true,
            'cancelled_at' => now(),
            'current_period_end' => now()->addDays(7)->toDateString(),
        ]);
    }
}
