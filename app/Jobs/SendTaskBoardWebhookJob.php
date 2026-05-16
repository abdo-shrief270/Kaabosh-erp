<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\TaskBoard\Models\TaskBoardWebhook;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Delivers a single webhook POST. Retried by the queue worker on
 * transient failures; permanent failures update the hook's last_error
 * so the UI can flag broken integrations.
 */
class SendTaskBoardWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // seconds — exponential via Laravel default

    public function __construct(
        public readonly int $webhookId,
        public readonly string $eventKey,
        public readonly array $payload,
    ) {
        $this->onQueue('webhooks');
    }

    public function handle(): void
    {
        /** @var TaskBoardWebhook|null $hook */
        $hook = TaskBoardWebhook::find($this->webhookId);
        if (! $hook || ! $hook->is_active) return;

        $body = $this->shape($hook);

        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'Kaabosh/1.0 (+webhook)',
            'X-Kaabosh-Event' => $this->eventKey,
            'X-Kaabosh-Delivery' => (string) \Illuminate\Support\Str::uuid(),
        ];

        if ($hook->secret && $hook->effectiveFormat() === 'generic') {
            $sig = hash_hmac('sha256', json_encode($body, JSON_UNESCAPED_SLASHES), $hook->secret);
            $headers['X-Kaabosh-Signature'] = 'sha256='.$sig;
        }

        $startedAt = microtime(true);
        try {
            $response = Http::timeout(10)->withHeaders($headers)->post($hook->url, $body);
            $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);

            $excerpt = \Illuminate\Support\Str::limit((string) $response->body(), 1800);

            if ($response->failed()) {
                $hook->forceFill([
                    'last_failed_at' => now(),
                    'last_error' => 'HTTP '.$response->status().' — '.\Illuminate\Support\Str::limit((string) $response->body(), 240),
                ])->save();
                $this->recordDelivery($hook, $body, $response->status(), $excerpt, $latencyMs, succeeded: false, error: null);
                // Retry for transient 5xx/timeouts.
                if (in_array($response->status(), [408, 429, 500, 502, 503, 504], true)) {
                    $this->release($this->backoff);
                    return;
                }
                return;
            }

            $hook->forceFill(['last_succeeded_at' => now(), 'last_error' => null])->save();
            $this->recordDelivery($hook, $body, $response->status(), $excerpt, $latencyMs, succeeded: true, error: null);
        } catch (Throwable $e) {
            $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);
            $hook->forceFill([
                'last_failed_at' => now(),
                'last_error' => \Illuminate\Support\Str::limit($e->getMessage(), 240),
            ])->save();
            $this->recordDelivery($hook, $body, null, null, $latencyMs, succeeded: false, error: $e->getMessage());
            throw $e;
        }
    }

    /**
     * Stash one row per delivery attempt so the SPA can render a history
     * + offer Replay. Payloads are capped at 1.8KB excerpts to keep the
     * table bounded.
     */
    private function recordDelivery(
        TaskBoardWebhook $hook, array $body, ?int $status, ?string $excerpt,
        int $latencyMs, bool $succeeded, ?string $error,
    ): void {
        try {
            \App\Domain\TaskBoard\Models\TaskBoardWebhookDelivery::create([
                'webhook_id' => $hook->id,
                'event_key' => $this->eventKey,
                'payload' => $body,
                'response_status' => $status,
                'response_body_excerpt' => $excerpt,
                'latency_ms' => $latencyMs,
                'succeeded' => $succeeded,
                'error' => $error ? \Illuminate\Support\Str::limit($error, 950) : null,
                'delivered_at' => now(),
            ]);
        } catch (Throwable) {
            // Don't let logging failures kill the actual delivery.
        }
    }

    /**
     * Format the payload for the receiver. Slack and Discord both accept
     * incoming-webhook URLs that expect their own JSON shape; for any
     * other URL we send a self-describing envelope.
     */
    private function shape(TaskBoardWebhook $hook): array
    {
        $format = $hook->effectiveFormat();
        $summary = (string) ($this->payload['summary'] ?? $this->eventKey);
        $taskRef = (string) ($this->payload['task']['reference'] ?? '');
        $taskTitle = (string) ($this->payload['task']['title'] ?? '');
        $actor = (string) ($this->payload['actor']['name'] ?? '');
        $url = (string) ($this->payload['task']['url'] ?? '');

        if ($format === 'slack') {
            $text = trim("*{$this->humanEvent()}* — {$taskRef} {$taskTitle}"
                .($actor ? " · {$actor}" : ''));
            return [
                'text' => $text,
                'attachments' => $url ? [['fallback' => $text, 'title' => $taskTitle ?: $this->eventKey, 'title_link' => $url, 'color' => $this->colour()]] : [],
            ];
        }

        if ($format === 'discord') {
            $title = trim("{$taskRef} {$taskTitle}") ?: $this->eventKey;
            return [
                'content' => $this->humanEvent().($actor ? " · {$actor}" : ''),
                'embeds' => [[
                    'title' => mb_substr($title, 0, 256),
                    'url' => $url ?: null,
                    'description' => mb_substr($summary, 0, 2048),
                    'color' => hexdec(ltrim($this->colour(), '#')) ?: 0x6366f1,
                ]],
            ];
        }

        // Generic — self-describing envelope, stable for downstream parsing.
        return [
            'event' => $this->eventKey,
            'delivered_at' => now()->toIso8601String(),
            'payload' => $this->payload,
        ];
    }

    private function humanEvent(): string
    {
        return match ($this->eventKey) {
            'task.created' => 'Task created',
            'task.moved' => 'Task moved',
            'task.updated' => 'Task updated',
            'task.deleted' => 'Task deleted',
            'task.completed' => 'Task completed',
            'comment.added' => 'New comment',
            'approval.requested' => 'Approval requested',
            'approval.decided' => 'Approval decided',
            default => $this->eventKey,
        };
    }

    private function colour(): string
    {
        return match ($this->eventKey) {
            'task.created' => '#10b981',
            'task.completed' => '#10b981',
            'task.deleted' => '#ef4444',
            'task.moved' => '#3b82f6',
            'approval.requested' => '#f59e0b',
            'approval.decided' => '#a855f7',
            'comment.added' => '#6366f1',
            default => '#6366f1',
        };
    }
}
