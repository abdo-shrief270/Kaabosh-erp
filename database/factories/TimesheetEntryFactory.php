<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Client\Models\Client;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\TimeTracking\Enums\TimesheetStatus;
use App\Domain\TimeTracking\Models\TimesheetEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TimesheetEntry> */
class TimesheetEntryFactory extends Factory
{
    protected $model = TimesheetEntry::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'client_id' => Client::factory(),
            'date' => today(),
            'task_description' => fake()->sentence(4),
            'hours' => fake()->randomFloat(2, 0.5, 8),
            'is_billable' => true,
            'status' => TimesheetStatus::Draft,
            'hourly_rate' => fake()->randomFloat(2, 50, 500),
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn () => ['status' => TimesheetStatus::Submitted]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => TimesheetStatus::Approved,
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => TimesheetStatus::Rejected]);
    }

    public function nonBillable(): static
    {
        return $this->state(fn () => ['is_billable' => false]);
    }
}
