<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Payroll\Models\LeaveType;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LeaveType> */
class LeaveTypeFactory extends Factory
{
    protected $model = LeaveType::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name_ar' => fake()->randomElement(['إجازة سنوية', 'إجازة مرضية', 'إجازة عارضة']),
            'name_en' => fake()->randomElement(['Annual Leave', 'Sick Leave', 'Casual Leave']),
            'code' => fake()->unique()->regexify('[A-Z]{2}[0-9]{2}'),
            'days_per_year' => 21,
            'is_paid' => true,
        ];
    }
}
