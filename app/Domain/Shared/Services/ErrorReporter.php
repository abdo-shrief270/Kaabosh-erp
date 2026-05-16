<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Sentry\State\Scope;

/**
 * Error reporting service with context enrichment.
 * Sends critical errors to external services (Sentry, Slack, etc.)
 *
 * Configure via config/error-reporting.php or .env:
 *   ERROR_REPORTING_DRIVER=slack (or sentry, log)
 *   SLACK_ERROR_WEBHOOK_URL=https://hooks.slack.com/...
 *   SENTRY_DSN=https://...@sentry.io/...
 */
class ErrorReporter
{
    /**
     * Report an exception with enriched context.
     */
    public static function report(\Throwable $e, array $context = []): void
    {
        $enriched = array_merge([
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile().':'.$e->getLine(),
            'trace' => mb_substr($e->getTraceAsString(), 0, 2000),
            'tenant_id' => app()->bound('tenant.id') ? app('tenant.id') : null,
            'user_id' => auth()->id(),
            'url' => request()?->fullUrl(),
            'method' => request()?->method(),
            'timestamp' => now()->toISOString(),
        ], $context);

        $driver = config('error-reporting.driver', 'log');

        match ($driver) {
            'slack' => self::reportToSlack($enriched),
            'sentry' => self::reportToSentry($e, $enriched),
            default => self::reportToLog($enriched),
        };
    }

    /**
     * Report a custom alert (not tied to an exception).
     */
    public static function alert(string $title, string $message, string $level = 'warning', array $context = []): void
    {
        $data = array_merge([
            'title' => $title,
            'message' => $message,
            'level' => $level,
            'tenant_id' => app()->bound('tenant.id') ? app('tenant.id') : null,
            'timestamp' => now()->toISOString(),
        ], $context);

        $driver = config('error-reporting.driver', 'log');

        if ($driver === 'slack') {
            self::sendSlackMessage($title, $message, $level, $context);
        }

        Log::log($level, "[ALERT] {$title}: {$message}", $data);
    }

    private static function reportToSlack(array $data): void
    {
        $webhookUrl = config('error-reporting.slack_webhook_url');
        if (! $webhookUrl) {
            return;
        }

        try {
            Http::timeout(5)->post($webhookUrl, [
                'blocks' => [
                    [
                        'type' => 'header',
                        'text' => ['type' => 'plain_text', 'text' => '🚨 Error: '.mb_substr($data['message'], 0, 100)],
                    ],
                    [
                        'type' => 'section',
                        'fields' => [
                            ['type' => 'mrkdwn', 'text' => "*Exception:*\n`{$data['exception']}`"],
                            ['type' => 'mrkdwn', 'text' => "*File:*\n`{$data['file']}`"],
                            ['type' => 'mrkdwn', 'text' => "*URL:*\n{$data['method']} {$data['url']}"],
                            ['type' => 'mrkdwn', 'text' => "*Tenant:*\n{$data['tenant_id']}"],
                        ],
                    ],
                    [
                        'type' => 'section',
                        'text' => ['type' => 'mrkdwn', 'text' => "```\n".mb_substr($data['trace'], 0, 500)."\n```"],
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to send error to Slack', ['error' => $e->getMessage()]);
        }
    }

    private static function sendSlackMessage(string $title, string $message, string $level, array $context): void
    {
        $webhookUrl = config('error-reporting.slack_webhook_url');
        if (! $webhookUrl) {
            return;
        }

        $emoji = match ($level) {
            'critical', 'emergency' => '🔴',
            'error' => '🚨',
            'warning' => '⚠️',
            default => 'ℹ️',
        };

        try {
            Http::timeout(5)->post($webhookUrl, [
                'text' => "{$emoji} *{$title}*\n{$message}",
            ]);
        } catch (\Throwable) {
            // Silent fail for alerts
        }
    }

    private static function reportToSentry(\Throwable $e, array $context): void
    {
        // If Sentry SDK is installed, use it directly
        if (function_exists('\\Sentry\\captureException')) {
            \Sentry\configureScope(function (Scope $scope) use ($context): void {
                if ($context['tenant_id']) {
                    $scope->setTag('tenant_id', (string) $context['tenant_id']);
                }
                if ($context['user_id']) {
                    $scope->setUser(['id' => (string) $context['user_id']]);
                }
            });
            \Sentry\captureException($e);
        } else {
            // Fallback to log
            self::reportToLog($context);
        }
    }

    private static function reportToLog(array $data): void
    {
        Log::error("[ErrorReporter] {$data['exception']}: {$data['message']}", $data);
    }
}
