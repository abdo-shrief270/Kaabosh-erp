<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Shared\Models\ApiUsageMeter;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Counts API calls per tenant per day.
 * Uses a cache buffer to batch increments (writes to DB every 50 calls).
 * This avoids a DB write on every single request.
 */
class MeterApiUsage
{
    private const BUFFER_SIZE = 50;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only meter authenticated tenant requests
        $tenantId = app('tenant.id');
        if (! $tenantId || ! $response->isSuccessful()) {
            return $response;
        }

        $this->bufferIncrement($tenantId);

        return $response;
    }

    private function bufferIncrement(int $tenantId): void
    {
        $key = "usage_buffer:{$tenantId}:".now()->toDateString();

        $count = Cache::increment($key);

        // Set TTL on first increment (expires end of day + 1 hour buffer)
        if ($count === 1) {
            $secondsUntilEndOfDay = now()->endOfDay()->diffInSeconds(now()) + 3600;
            Cache::put($key, $count, $secondsUntilEndOfDay);
        }

        // Flush to DB every BUFFER_SIZE calls
        if ($count % self::BUFFER_SIZE === 0) {
            try {
                ApiUsageMeter::incrementFor($tenantId, 'api_calls', self::BUFFER_SIZE);
                Cache::decrement($key, self::BUFFER_SIZE);
            } catch (\Throwable $e) {
                // Don't break the request if metering fails
                logger()->warning('Usage metering flush failed', ['error' => $e->getMessage()]);
            }
        }
    }
}
