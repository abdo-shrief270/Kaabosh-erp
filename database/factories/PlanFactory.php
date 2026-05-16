<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Subscription\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Plan> */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $priceMonthly = fake()->randomFloat(2, 99, 999);

        return [
            'name_en' => fake()->unique()->randomElement(['Basic', 'Standard', 'Premium', 'Ultimate', 'Growth', 'Scale']),
            'name_ar' => fake()->unique()->randomElement(['أساسي', 'قياسي', 'متميز', 'نهائي', 'نمو', 'توسع']),
            'slug' => fake()->unique()->slug(2),
            'description_en' => fake()->optional(0.7)->sentence(),
            'description_ar' => fake()->optional(0.7)->randomElement([
                'خطة مناسبة للشركات الصغيرة',
                'خطة احترافية للشركات المتوسطة',
                'خطة شاملة للمؤسسات الكبيرة',
            ]),
            'price_monthly' => $priceMonthly,
            'price_annual' => round($priceMonthly * 10, 2),
            'currency' => 'EGP',
            'trial_days' => 14,
            'limits' => [
                'max_users' => fake()->randomElement([5, 10, 25, 50]),
                'max_clients' => fake()->randomElement([50, 100, 200, 500]),
                'max_storage_bytes' => fake()->randomElement([2147483648, 5368709120, 10737418240]),
                'max_invoices_per_month' => fake()->randomElement([100, 500, 1000, 5000]),
            ],
            'features' => [
                'e_invoice' => fake()->boolean(50),
                'api_access' => fake()->boolean(40),
                'custom_reports' => fake()->boolean(60),
                'client_portal' => fake()->boolean(30),
                'priority_support' => fake()->boolean(20),
            ],
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function starter(): static
    {
        return $this->state(fn () => [
            'name_en' => 'Starter',
            'name_ar' => 'أساسي',
            'slug' => 'starter',
            'price_monthly' => 299.00,
            'price_annual' => 2990.00,
            'trial_days' => 14,
            'limits' => [
                'max_users' => 5,
                'max_clients' => 50,
                'max_storage_bytes' => 2147483648,
                'max_invoices_per_month' => 200,
            ],
            'features' => [
                'e_invoice' => false,
                'api_access' => false,
                'custom_reports' => true,
                'client_portal' => false,
                'priority_support' => false,
            ],
            'sort_order' => 1,
        ]);
    }

    public function professional(): static
    {
        return $this->state(fn () => [
            'name_en' => 'Professional',
            'name_ar' => 'احترافي',
            'slug' => 'professional',
            'price_monthly' => 599.00,
            'price_annual' => 5990.00,
            'trial_days' => 14,
            'limits' => [
                'max_users' => 15,
                'max_clients' => 200,
                'max_storage_bytes' => 10737418240,
                'max_invoices_per_month' => 1000,
            ],
            'features' => [
                'e_invoice' => true,
                'api_access' => true,
                'custom_reports' => true,
                'client_portal' => true,
                'priority_support' => false,
            ],
            'sort_order' => 2,
        ]);
    }

    public function enterprise(): static
    {
        return $this->state(fn () => [
            'name_en' => 'Enterprise',
            'name_ar' => 'مؤسسات',
            'slug' => 'enterprise',
            'price_monthly' => 1499.00,
            'price_annual' => 14990.00,
            'trial_days' => 30,
            'limits' => [
                'max_users' => 100,
                'max_clients' => -1,
                'max_storage_bytes' => 53687091200,
                'max_invoices_per_month' => -1,
            ],
            'features' => [
                'e_invoice' => true,
                'api_access' => true,
                'custom_reports' => true,
                'client_portal' => true,
                'priority_support' => true,
            ],
            'sort_order' => 3,
        ]);
    }

    public function freeTrial(): static
    {
        return $this->state(fn () => [
            'name_en' => 'Free Trial',
            'name_ar' => 'تجربة مجانية',
            'slug' => 'free_trial',
            'price_monthly' => 0,
            'price_annual' => 0,
            'trial_days' => 14,
            'limits' => [
                'max_users' => 2,
                'max_clients' => 10,
                'max_storage_bytes' => 536870912,
                'max_invoices_per_month' => 20,
            ],
            'features' => [
                'e_invoice' => false,
                'api_access' => false,
                'custom_reports' => false,
                'client_portal' => false,
                'priority_support' => false,
            ],
            'sort_order' => 0,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
