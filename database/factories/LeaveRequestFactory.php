<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Payroll\Enums\LeaveStatus;
use App\Domain\Payroll\Models\Employee;
use App\Domain\Payroll\Models\LeaveRequest;
use App\Domain\Payroll\Models\LeaveType;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LeaveRequest> */
class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('now', '+1 month');
        $days = fake()->numberBetween(1, 5);

        return [
            'tenant_id' => Tenant::factory(),
            'employee_id' => Employee::factory(),
            'leave_type_id' => LeaveType::factory(),
            'start_date' => $startDate,
            'end_date' => (clone $startDate)->modify("+{$days} days"),
            'days' => $days,
            'status' => LeaveStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => LeaveStatus::Approved]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => LeaveStatus::Rejected]);
    }
}
