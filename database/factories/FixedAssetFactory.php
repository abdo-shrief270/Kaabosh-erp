<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\FixedAssets\Models\AssetCategory;
use App\Domain\FixedAssets\Models\FixedAsset;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FixedAsset> */
class FixedAssetFactory extends Factory
{
    protected $model = FixedAsset::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $cost = fake()->randomFloat(2, 1000, 100000);
        $salvage = round($cost * 0.1, 2);
        $date = fake()->dateTimeBetween('-3 years', '-1 month');

        return [
            'tenant_id' => Tenant::factory(),
            'category_id' => AssetCategory::factory(),
            'code' => 'FA-'.fake()->unique()->numerify('#####'),
            'name_ar' => 'أصل '.fake()->word(),
            'name_en' => fake()->words(2, true),
            'status' => 'active',
            'acquisition_date' => $date,
            'acquisition_cost' => $cost,
            'depreciation_method' => 'straight_line',
            'useful_life_years' => fake()->randomElement([3, 5, 7, 10]),
            'salvage_value' => $salvage,
            'accumulated_depreciation' => '0.00',
            'book_value' => $cost,
            'depreciation_start_date' => $date,
        ];
    }

    public function disposed(): static
    {
        return $this->state(['status' => 'disposed']);
    }
}
