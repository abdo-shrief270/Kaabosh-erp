<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\FixedAssets\Models\AssetCategory;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AssetCategory> */
class AssetCategoryFactory extends Factory
{
    protected $model = AssetCategory::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name_ar' => 'فئة '.fake()->word(),
            'name_en' => fake()->words(2, true).' Category',
            'code' => 'CAT-'.fake()->unique()->numerify('###'),
            'depreciation_method' => 'straight_line',
            'default_useful_life_years' => fake()->randomElement([3, 5, 7, 10]),
            'default_salvage_percent' => fake()->randomElement([0, 5, 10]),
        ];
    }
}
