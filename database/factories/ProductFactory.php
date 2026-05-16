<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Inventory\Enums\ValuationMethod;
use App\Domain\Inventory\Models\Product;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'category_id' => null,
            'sku' => fake()->unique()->bothify('PRD-######'),
            'name_ar' => fake()->randomElement([
                'شاشة كمبيوتر',
                'لوحة مفاتيح',
                'ماوس لاسلكي',
                'طابعة ليزر',
                'كرسي مكتب',
                'مكتب خشبي',
                'ورق طباعة',
                'حبر طابعة',
            ]),
            'name_en' => fake()->randomElement([
                'Computer Monitor',
                'Keyboard',
                'Wireless Mouse',
                'Laser Printer',
                'Office Chair',
                'Wooden Desk',
                'Printing Paper',
                'Printer Ink',
            ]),
            'description' => fake()->optional(0.5)->sentence(),
            'unit_of_measure' => fake()->randomElement(['unit', 'kg', 'meter', 'box', 'pack']),
            'purchase_price' => fake()->randomFloat(2, 10, 5000),
            'selling_price' => fake()->randomFloat(2, 20, 8000),
            'vat_rate' => '14.00',
            'reorder_level' => fake()->numberBetween(5, 50),
            'current_stock' => fake()->numberBetween(0, 200),
            'valuation_method' => ValuationMethod::WeightedAverage,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function lowStock(): static
    {
        return $this->state(fn () => [
            'reorder_level' => 10,
            'current_stock' => fake()->numberBetween(0, 5),
        ]);
    }

    public function withStock(int $qty, string $price): static
    {
        return $this->state(fn () => [
            'current_stock' => $qty,
            'purchase_price' => $price,
        ]);
    }

    public function fifo(): static
    {
        return $this->state(fn () => [
            'valuation_method' => ValuationMethod::Fifo,
        ]);
    }
}
