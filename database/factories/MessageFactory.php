<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Client\Models\Client;
use App\Domain\ClientPortal\Enums\MessageDirection;
use App\Domain\ClientPortal\Models\Message;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Message> */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'client_id' => Client::factory(),
            'user_id' => User::factory(),
            'direction' => MessageDirection::Inbound,
            'subject' => fake()->sentence(),
            'body' => fake()->paragraphs(2, true),
            'read_at' => null,
        ];
    }

    public function inbound(): static
    {
        return $this->state(fn () => ['direction' => MessageDirection::Inbound]);
    }

    public function outbound(): static
    {
        return $this->state(fn () => ['direction' => MessageDirection::Outbound]);
    }

    public function read(): static
    {
        return $this->state(fn () => ['read_at' => now()]);
    }
}
