<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Expenses\Enums\ExpenseStatus;
use App\Domain\Expenses\Models\ExpenseReport;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ExpenseReport> */
class ExpenseReportFactory extends Factory
{
    protected $model = ExpenseReport::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $periodFrom = fake()->dateTimeBetween('-3 months', '-1 month');

        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'title' => fake()->randomElement([
                'تقرير مصروفات شهر '.fake()->monthName(),
                'مصروفات رحلة عمل',
                'مصروفات مشروع',
                'تقرير مصروفات أسبوعي',
            ]),
            'status' => ExpenseStatus::Draft,
            'period_from' => $periodFrom,
            'period_to' => fake()->dateTimeBetween($periodFrom, 'now'),
            'total_amount' => 0,
            'total_vat' => 0,
        ];
    }
}
