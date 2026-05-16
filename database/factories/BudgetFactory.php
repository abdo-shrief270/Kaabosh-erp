<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Accounting\Models\Budget;
use Illuminate\Database\Eloquent\Factories\Factory;

class BudgetFactory extends Factory
{
    protected $model = Budget::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true).' Budget',
            'name_ar' => 'ميزانية '.$this->faker->word(),
            'status' => 'draft',
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
