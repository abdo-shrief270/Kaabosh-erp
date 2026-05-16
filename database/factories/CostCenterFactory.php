<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Accounting\Enums\CostCenterType;
use App\Domain\Accounting\Models\CostCenter;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CostCenter> */
class CostCenterFactory extends Factory
{
    protected $model = CostCenter::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'parent_id' => null,
            'code' => 'CC-'.(string) fake()->unique()->numberBetween(1000, 9999),
            'name_ar' => fake()->randomElement([
                'الإدارة المالية',
                'قسم المبيعات',
                'قسم الموارد البشرية',
                'قسم التسويق',
                'قسم تكنولوجيا المعلومات',
                'الإدارة العامة',
                'قسم الإنتاج',
                'قسم الجودة',
            ]),
            'name_en' => fake()->randomElement([
                'Finance Department',
                'Sales Department',
                'Human Resources',
                'Marketing Department',
                'IT Department',
                'General Administration',
                'Production Department',
                'Quality Department',
            ]),
            'type' => fake()->randomElement(CostCenterType::cases()),
            'is_active' => true,
            // Column is NOT NULL with default 0 — factory must always supply
            // a concrete value since Eloquent INSERTs the null explicitly.
            'budget' => fake()->boolean(50) ? fake()->randomFloat(2, 10000, 100000) : 0,
            'description' => fake()->optional(0.3)->sentence(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function department(): static
    {
        return $this->state(fn () => [
            'type' => CostCenterType::Department,
        ]);
    }

    public function project(): static
    {
        return $this->state(fn () => [
            'type' => CostCenterType::Project,
        ]);
    }

    public function withBudget(float $amount): static
    {
        return $this->state(fn () => [
            'budget' => $amount,
        ]);
    }
}
