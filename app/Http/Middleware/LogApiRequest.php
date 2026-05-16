<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Jobs\LogApiRequestJob;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs API requests with timing, status, user context.
 * Sensitive data (passwords, tokens) is automatically redacted.
 */
class LogApiRequest
{
    private const REDACTED_FIELDS = [
        'password', 'password_confirmation', 'token', 'secret',
        'authorization', 'cookie', 'api_key', 'credit_card',
        'client_secret',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('api.logging.enabled', true)) {
            return $next($request);
        }

        // Skip excluded paths
        $path = $request->path();
        foreach (config('api.logging.exclude_paths', []) as $excluded) {
            if (str_starts_with($path, $excluded)) {
                return $next($request);
            }
        }

        // Skip excluded methods
        if (in_array($request->method(), config('api.logging.exclude_methods', ['OPTIONS']))) {
            return $next($request);
        }

        $startTime = microtime(true);

        $response = $next($request);

        $durationMs = (int) round((microtime(true) - $startTime) * 1000);

        try {
            LogApiRequestJob::dispatch([
                'request_id' => $response->headers->get('X-Request-Id', bin2hex(random_bytes(12))),
                'method' => $request->method(),
                'path' => '/'.ltrim($path, '/'),
                'status_code' => $response->getStatusCode(),
                'duration_ms' => $durationMs,
                'ip' => $request->ip(),
                'user_agent' => mb_substr($request->userAgent() ?? '', 0, 500),
                'user_id' => $request->user()?->id,
                'tenant_id' => $request->header('X-Tenant'),
                'request_size' => (int) $request->header('Content-Length', '0'),
                'response_size' => strlen($response->getContent() ?: ''),
                'request_headers' => $this->redactHeaders($request),
                'request_body' => $this->redactBody($request),
                'error_message' => $response->getStatusCode() >= 400
                    ? mb_substr($this->extractErrorMessage($response), 0, 1000)
                    : null,
                'created_at' => now(),
            ])->onQueue('logging');
        } catch (\Throwable $e) {
            // Never let logging break the request
            logger()->warning('API request logging failed', ['error' => $e->getMessage()]);
        }

        // Log slow requests as warnings
        $threshold = config('api.logging.slow_threshold_ms', 1000);
        if ($durationMs >= $threshold) {
            logger()->warning('Slow API request', [
                'path' => $path,
                'method' => $request->method(),
                'duration_ms' => $durationMs,
                'status' => $response->getStatusCode(),
            ]);
        }

        return $response;
    }

    private function redactHeaders(Request $request): array
    {
        $headers = [];
        $safe = ['accept', 'accept-language', 'content-type', 'x-tenant', 'x-request-id', 'origin', 'referer'];

        foreach ($safe as $key) {
            if ($value = $request->header($key)) {
                $headers[$key] = $value;
            }
        }

        return $headers;
    }

    private function redactBody(Request $request): ?array
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            return null;
        }

        $body = $request->all();
        if (empty($body)) {
            return null;
        }

        return $this->redactArray($body);
    }

    private function redactArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), self::REDACTED_FIELDS)) {
                $data[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $data[$key] = $this->redactArray($value);
            } elseif (is_string($value) && strlen($value) > 500) {
                $data[$key] = mb_substr($value, 0, 200).'...[truncated]';
            }
        }

        return $data;
    }

    private function extractErrorMessage(Response $response): string
    {
        $content = $response->getContent();
        if (! $content) {
            return '';
        }

        $json = json_decode($content, true);

        return $json['message'] ?? $json['error'] ?? '';
    }
}
