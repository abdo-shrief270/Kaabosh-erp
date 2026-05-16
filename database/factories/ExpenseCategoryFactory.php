<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Expenses\Models\ExpenseCategory;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ExpenseCategory> */
class ExpenseCategoryFactory extends Factory
{
    protected $model = ExpenseCategory::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name_ar' => fake()->randomElement([
                'سفر وتنقلات',
                'مستلزمات مكتبية',
                'وجبات وضيافة',
                'اتصالات وإنترنت',
                'صيانة وإصلاح',
                'إيجار',
                'مرافق',
                'تدريب وتطوير',
            ]),
            'name_en' => fake()->randomElement([
                'Travel & Transport',
                'Office Supplies',
                'Meals & Hospitality',
                'Telecom & Internet',
                'Maintenance & Repair',
                'Rent',
                'Utilities',
                'Training & Development',
            ]),
            'code' => fake()->unique()->regexify('[A-Z]{3}-[0-9]{3}'),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
