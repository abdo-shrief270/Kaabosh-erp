<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\AccountsPayable\Models\Vendor;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Vendor> */
class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name_ar' => 'مورد '.fake()->company(),
            'name_en' => fake()->company(),
            'code' => 'V-'.fake()->unique()->numerify('####'),
            'tax_id' => fake()->numerify('#########'),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address_ar' => fake()->address(),
            'city' => fake()->city(),
            'country' => 'EG',
            'payment_terms' => fake()->randomElement(['net_15', 'net_30', 'net_60']),
            'credit_limit' => fake()->randomFloat(2, 1000, 100000),
            'currency' => 'EGP',
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
