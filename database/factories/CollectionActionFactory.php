<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Client\Models\Client;
use App\Domain\Collection\Enums\CollectionActionType;
use App\Domain\Collection\Enums\CollectionOutcome;
use App\Domain\Collection\Models\CollectionAction;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CollectionAction> */
class CollectionActionFactory extends Factory
{
    protected $model = CollectionAction::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'invoice_id' => Invoice::factory(),
            'client_id' => Client::factory(),
            'action_type' => fake()->randomElement(CollectionActionType::cases()),
            'action_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'notes' => fake()->optional(0.7)->sentence(),
            'outcome' => fake()->randomElement(CollectionOutcome::cases()),
            'commitment_date' => fake()->optional(0.3)->dateTimeBetween('now', '+30 days'),
            'commitment_amount' => fake()->optional(0.3)->randomFloat(2, 100, 50000),
            'performed_by' => User::factory(),
        ];
    }

    public function call(): static
    {
        return $this->state(fn () => [
            'action_type' => CollectionActionType::Call,
        ]);
    }

    public function writeOff(): static
    {
        return $this->state(fn () => [
            'action_type' => CollectionActionType::WriteOff,
        ]);
    }

    public function withOutcome(CollectionOutcome $outcome): static
    {
        return $this->state(fn () => [
            'outcome' => $outcome,
        ]);
    }
}
