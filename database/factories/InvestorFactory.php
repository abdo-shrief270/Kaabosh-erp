<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Investor\Models\Investor;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Investor> */
class InvestorFactory extends Factory
{
    protected $model = Investor::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'join_date' => fake()->dateTimeBetween('-2 years', 'now'),
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
