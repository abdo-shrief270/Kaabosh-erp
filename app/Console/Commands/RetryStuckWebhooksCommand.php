<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Webhook\Models\WebhookDelivery;
use App\Jobs\DispatchWebhookJob;
use Illuminate\Console\Command;

class RetryStuckWebhooksCommand extends Command
{
    protected $signature = 'webhooks:retry-stuck {--hours=2 : Re-queue deliveries stuck longer than N hours}';

    protected $description = 'Retry webhook deliveries stuck in "retrying" status past their next_retry_at';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');

        $stuck = WebhookDelivery::where('status', 'retrying')
            ->where('next_retry_at', '<=', now()->subHours($hours))
            ->get();

        if ($stuck->isEmpty()) {
            $this->info('No stuck webhook deliveries found.');

            return self::SUCCESS;
        }

        $this->info("Found {$stuck->count()} stuck webhook delivery(ies). Re-queuing...");

        foreach ($stuck as $delivery) {
            DispatchWebhookJob::dispatch($delivery->id);
            $this->line("  Re-queued delivery #{$delivery->id} (event: {$delivery->event}, attempt: {$delivery->attempt})");
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
