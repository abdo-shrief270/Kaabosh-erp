<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Webhook\Models\WebhookEndpoint;
use Illuminate\Database\Eloquent\Factories\Factory;

class WebhookEndpointFactory extends Factory
{
    protected $model = WebhookEndpoint::class;

    public function definition(): array
    {
        return [
            'url' => $this->faker->url(),
            'secret' => $this->faker->sha256(),
            'events' => ['invoice.created', 'payment.received'],
            'is_active' => true,
        ];
    }
}
