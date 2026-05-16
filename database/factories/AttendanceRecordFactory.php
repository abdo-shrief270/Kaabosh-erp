<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Payroll\Enums\AttendanceStatus;
use App\Domain\Payroll\Models\AttendanceRecord;
use App\Domain\Payroll\Models\Employee;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AttendanceRecord> */
class AttendanceRecordFactory extends Factory
{
    protected $model = AttendanceRecord::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'employee_id' => Employee::factory(),
            'date' => fake()->date(),
            'check_in' => '09:00',
            'check_out' => '17:00',
            'hours_worked' => 8.00,
            'overtime_hours' => 0.00,
            'status' => AttendanceStatus::Present,
        ];
    }
}
