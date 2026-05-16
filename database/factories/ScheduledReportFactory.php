<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Accounting\Models\ScheduledReport;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduledReportFactory extends Factory
{
    protected $model = ScheduledReport::class;

    public function definition(): array
    {
        return [
            'report_type' => $this->faker->randomElement([
                'trial_balance', 'income_statement', 'balance_sheet',
                'cash_flow', 'vat_return',
            ]),
            'report_config' => [
                'from' => now()->startOfMonth()->format('Y-m-d'),
                'to' => now()->endOfMonth()->format('Y-m-d'),
            ],
            'schedule_type' => $this->faker->randomElement(['daily', 'weekly', 'monthly', 'quarterly']),
            'schedule_day' => $this->faker->numberBetween(1, 28),
            'schedule_time' => '08:00',
            'format' => 'pdf',
            'recipients' => [$this->faker->safeEmail()],
            'subject_template' => null,
            'is_active' => true,
            'next_send_at' => now()->addDay(),
        ];
    }
}
