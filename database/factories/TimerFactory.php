<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Tenant\Models\Tenant;
use App\Domain\TimeTracking\Models\Timer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Timer> */
class TimerFactory extends Factory
{
    protected $model = Timer::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'task_description' => fake()->sentence(4),
            'started_at' => now()->subHours(2),
            'is_running' => true,
        ];
    }

    public function stopped(): static
    {
        return $this->state(fn () => [
            'stopped_at' => now(),
            'is_running' => false,
        ]);
    }
}
