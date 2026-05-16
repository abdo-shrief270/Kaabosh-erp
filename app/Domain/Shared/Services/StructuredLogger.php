<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use Illuminate\Support\Facades\Log;

/**
 * Structured logging helper that automatically enriches log entries
 * with trace IDs, tenant context, user context, and request metadata.
 *
 * Usage:
 *   StructuredLogger::info('Invoice created', ['invoice_id' => 123]);
 *   StructuredLogger::error('Payment failed', ['gateway' => 'paymob', 'error' => $e->getMessage()]);
 */
class StructuredLogger
{
    /**
     * Log with automatic context enrichment.
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        $enriched = array_merge(self::buildContext(), $context);
        Log::log($level, $message, $enriched);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::log('critical', $message, $context);
    }

    /**
     * Log with performance timing.
     */
    public static function timed(string $message, callable $callback, array $context = []): mixed
    {
        $start = microtime(true);
        $result = $callback();
        $durationMs = round((microtime(true) - $start) * 1000, 2);

        self::info($message, array_merge($context, ['duration_ms' => $durationMs]));

        return $result;
    }

    /**
     * Build enrichment context from current request.
     */
    private static function buildContext(): array
    {
        $context = [
            'trace_id' => self::getTraceId(),
            'timestamp' => now()->toISOString(),
        ];

        // Tenant context
        $tenantId = app()->bound('tenant.id') ? app('tenant.id') : null;
        if ($tenantId) {
            $context['tenant_id'] = $tenantId;
        }

        // User context
        try {
            $user = auth()->user();
            if ($user) {
                $context['user_id'] = $user->id;
                $context['user_role'] = $user->role?->value ?? $user->role ?? null;
            }
        } catch (\Throwable) {
            // Auth not available
        }

        // Request context (if in HTTP context)
        try {
            $request = request();
            if ($request) {
                $context['request_method'] = $request->method();
                $context['request_path'] = $request->path();
                $context['ip'] = $request->ip();
            }
        } catch (\Throwable) {
            // Not in HTTP context
        }

        return $context;
    }

    /**
     * Get or generate a trace ID for the current request.
     */
    private static function getTraceId(): string
    {
        static $traceId = null;

        if ($traceId === null) {
            try {
                $traceId = request()?->header('X-Request-Id') ?? bin2hex(random_bytes(12));
            } catch (\Throwable) {
                $traceId = bin2hex(random_bytes(12));
            }
        }

        return $traceId;
    }
}
