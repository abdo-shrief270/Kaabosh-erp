<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Webhook\Models\WebhookDelivery;
use App\Domain\Webhook\Services\WebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1; // Retries handled by WebhookService

    public int $timeout = 30;

    public function __construct(
        public readonly int $deliveryId,
    ) {
        $this->onQueue('webhooks');
    }

    public function handle(): void
    {
        $delivery = WebhookDelivery::find($this->deliveryId);

        if (! $delivery) {
            return;
        }

        WebhookService::send($delivery);
    }
}
