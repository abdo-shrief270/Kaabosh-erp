<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Client\Models\Client;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Client> */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->company(),
            'trade_name' => fake()->optional(0.7)->company(),
            'tax_id' => (string) fake()->unique()->numberBetween(100000000, 999999999),
            'commercial_register' => (string) fake()->numberBetween(10000, 99999),
            'activity_type' => fake()->randomElement([
                'تجارة عامة',
                'مقاولات',
                'استيراد وتصدير',
                'خدمات استشارية',
                'صناعة',
                'عقارات',
                'تكنولوجيا معلومات',
                'أغذية ومشروبات',
            ]),
            'address' => fake()->address(),
            'city' => fake()->randomElement(['القاهرة', 'الإسكندرية', 'الجيزة', 'المنصورة', 'أسيوط', 'طنطا']),
            'phone' => '+20'.fake()->numberBetween(1000000000, 1999999999),
            'email' => fake()->unique()->companyEmail(),
            'contact_person' => fake()->name(),
            'contact_phone' => '+20'.fake()->numberBetween(1000000000, 1999999999),
            'notes' => fake()->optional(0.3)->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function withoutTaxId(): static
    {
        return $this->state(fn () => [
            'tax_id' => null,
        ]);
    }
}
