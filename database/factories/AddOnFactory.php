<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Subscription\Enums\AddOnBillingCycle;
use App\Domain\Subscription\Enums\AddOnType;
use App\Domain\Subscription\Models\AddOn;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AddOn> */
class AddOnFactory extends Factory
{
    protected $model = AddOn::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(2),
            'name_en' => fake()->randomElement(['+5 Users', '+5 GB Storage', '+200 Invoices', 'Advanced Reports', 'White Label']),
            'name_ar' => fake()->randomElement(['+5 مستخدمين', '+5 جيجا تخزين', '+200 فاتورة', 'تقارير متقدمة', 'علامة بيضاء']),
            'description_en' => fake()->optional(0.6)->sentence(),
            'description_ar' => fake()->optional(0.6)->randomElement(['ميزة إضافية', 'رفع الحدود', 'باقة رصيد']),
            'type' => AddOnType::Boost->value,
            'billing_cycle' => AddOnBillingCycle::Monthly->value,
            'boost' => ['max_users' => 5],
            'feature_slug' => null,
            'credit_kind' => null,
            'credit_quantity' => null,
            'price_monthly' => fake()->randomFloat(2, 49, 299),
            'price_annual' => fake()->randomFloat(2, 490, 2990),
            'price_once' => 0,
            'currency' => 'EGP',
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function feature(string $slug = 'advanced_reports'): self
    {
        return $this->state(fn () => [
            'type' => AddOnType::Feature->value,
            'feature_slug' => $slug,
            'boost' => null,
        ]);
    }

    public function creditPack(string $kind = 'sms', int $quantity = 1000): self
    {
        return $this->state(fn () => [
            'type' => AddOnType::CreditPack->value,
            'billing_cycle' => AddOnBillingCycle::Once->value,
            'credit_kind' => $kind,
            'credit_quantity' => $quantity,
            'boost' => null,
            'price_monthly' => 0,
            'price_annual' => 0,
            'price_once' => fake()->randomFloat(2, 99, 499),
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
