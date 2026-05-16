<?php

declare(strict_types=1);

namespace App\Domain\Webhook\Services;

use App\Domain\Webhook\Models\WebhookDelivery;
use App\Domain\Webhook\Models\WebhookEndpoint;
use App\Jobs\DispatchWebhookJob;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class WebhookService
{
    /**
     * Available webhook events.
     */
    public const EVENTS = [
        'invoice.created',
        'invoice.updated',
        'invoice.sent',
        'payment.received',
        'client.created',
        'client.updated',
        'journal_entry.created',
        'eta.submitted',
        'eta.accepted',
        'eta.rejected',
        'subscription.activated',
        'subscription.cancelled',
        'subscription.expired',
    ];

    /**
     * Dispatch a webhook event to all matching endpoints for a tenant.
     */
    public static function dispatch(int $tenantId, string $event, array $data): void
    {
        $endpoints = WebhookEndpoint::where('tenant_id', $tenantId)
            ->active()
            ->get()
            ->filter(fn (WebhookEndpoint $ep) => $ep->listensTo($event));

        foreach ($endpoints as $endpoint) {
            $delivery = WebhookDelivery::create([
                'endpoint_id' => $endpoint->id,
                'event' => $event,
                'payload' => [
                    'event' => $event,
                    'timestamp' => now()->toISOString(),
                    'data' => $data,
                ],
                'status' => 'pending',
                'attempt' => 1,
            ]);

            DispatchWebhookJob::dispatch($delivery->id);
        }
    }

    /**
     * Send a webhook delivery.
     */
    public static function send(WebhookDelivery $delivery): void
    {
        $endpoint = $delivery->endpoint;
        if (! $endpoint || ! $endpoint->is_active) {
            $delivery->update(['status' => 'failed', 'error_message' => 'Endpoint disabled or deleted.']);

            return;
        }

        $payload = json_encode($delivery->payload);
        $signature = hash_hmac('sha256', $payload, $endpoint->secret);

        $timeout = config('api.webhooks.timeout', 10);

        $startTime = microtime(true);

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $endpoint->url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'User-Agent: Muhasebi-Webhook/1.0',
                    config('api.webhooks.signing_secret_header', 'X-Muhasebi-Signature').': sha256='.$signature,
                    'X-Webhook-Event: '.$delivery->event,
                    'X-Webhook-Delivery-Id: '.$delivery->id,
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $responseBody = curl_exec($ch);
            $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            $durationMs = (int) round((microtime(true) - $startTime) * 1000);

            if ($error) {
                throw new \RuntimeException("cURL error: {$error}");
            }

            $delivery->update([
                'status_code' => $statusCode,
                'response_body' => mb_substr((string) $responseBody, 0, 2000),
                'duration_ms' => $durationMs,
            ]);

            if ($statusCode >= 200 && $statusCode < 300) {
                $delivery->update(['status' => 'success']);
                $endpoint->resetFailures();
            } else {
                throw new \RuntimeException("HTTP {$statusCode}");
            }
        } catch (\Throwable $e) {
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);

            $delivery->update([
                'duration_ms' => $durationMs,
                'error_message' => mb_substr($e->getMessage(), 0, 1000),
            ]);

            $endpoint->recordFailure();
            self::scheduleRetry($delivery);
        }
    }

    /**
     * Schedule a retry with exponential backoff.
     */
    private static function scheduleRetry(WebhookDelivery $delivery): void
    {
        $maxRetries = config('api.webhooks.max_retries', 3);
        $retryDelays = config('api.webhooks.retry_delay_minutes', [1, 5, 30]);

        if ($delivery->attempt >= $maxRetries) {
            $delivery->update(['status' => 'failed']);

            return;
        }

        $nextAttempt = $delivery->attempt; // 0-indexed for delay array
        $delayMinutes = $retryDelays[$nextAttempt - 1] ?? end($retryDelays);

        $delivery->update([
            'status' => 'retrying',
            'attempt' => $delivery->attempt + 1,
            'next_retry_at' => now()->addMinutes($delayMinutes),
        ]);

        DispatchWebhookJob::dispatch($delivery->id)
            ->delay(now()->addMinutes($delayMinutes));
    }

    // ── Admin CRUD ────────────────────────────────────────────

    public function listEndpoints(int $tenantId): LengthAwarePaginator
    {
        return WebhookEndpoint::where('tenant_id', $tenantId)
            ->withCount('deliveries')
            ->latest()
            ->paginate(20);
    }

    public function createEndpoint(int $tenantId, array $data): WebhookEndpoint
    {
        return WebhookEndpoint::create([
            'tenant_id' => $tenantId,
            'url' => $data['url'],
            'secret' => $data['secret'] ?? Str::random(48),
            'events' => $data['events'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function updateEndpoint(WebhookEndpoint $endpoint, array $data): WebhookEndpoint
    {
        $endpoint->update(array_filter([
            'url' => $data['url'] ?? $endpoint->url,
            'events' => $data['events'] ?? $endpoint->events,
            'description' => $data['description'] ?? $endpoint->description,
            'is_active' => $data['is_active'] ?? $endpoint->is_active,
        ], fn ($v) => $v !== null));

        if (isset($data['regenerate_secret']) && $data['regenerate_secret']) {
            $endpoint->update(['secret' => Str::random(48)]);
        }

        return $endpoint->fresh();
    }

    public function getDeliveries(WebhookEndpoint $endpoint): LengthAwarePaginator
    {
        return $endpoint->deliveries()->latest()->paginate(30);
    }
}
