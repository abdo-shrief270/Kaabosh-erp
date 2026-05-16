<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Inventory\Models\ProductCategory;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductCategory> */
class ProductCategoryFactory extends Factory
{
    protected $model = ProductCategory::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name_ar' => fake()->randomElement([
                'إلكترونيات',
                'أثاث مكتبي',
                'مستلزمات مكتبية',
                'أجهزة كمبيوتر',
                'قطع غيار',
                'مواد خام',
                'منتجات تامة',
                'مواد تعبئة',
            ]),
            'name_en' => fake()->randomElement([
                'Electronics',
                'Office Furniture',
                'Office Supplies',
                'Computers',
                'Spare Parts',
                'Raw Materials',
                'Finished Goods',
                'Packaging Materials',
            ]),
            'code' => fake()->unique()->bothify('CAT-###'),
            'parent_id' => null,
        ];
    }

    public function withParent(ProductCategory $parent): static
    {
        return $this->state(fn () => [
            'parent_id' => $parent->id,
            'tenant_id' => $parent->tenant_id,
        ]);
    }
}
