<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Idempotency key middleware.
 * Prevents duplicate processing of the same request (e.g., double payment submissions).
 *
 * Client sends: Idempotency-Key: <uuid>
 * Server stores the response and returns it on duplicate requests.
 *
 * Only applies to non-GET/HEAD methods (state-changing requests).
 * Keys expire after 24 hours.
 */
class IdempotencyKey
{
    private const TTL = 86400; // 24 hours

    private const HEADER = 'Idempotency-Key';

    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to state-changing methods
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            return $next($request);
        }

        $key = $request->header(self::HEADER);
        if (! $key) {
            return $next($request);
        }

        if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $key)) {
            return response()->json(['error' => 'Idempotency-Key must be a valid UUID v4'], 422);
        }

        $cacheKey = "idempotency:{$key}";

        // Check for existing response
        $cached = Cache::get($cacheKey);
        if ($cached) {
            $response = response(decrypt($cached['body']), $cached['status'])
                ->withHeaders(['X-Idempotency-Replay' => 'true']);

            return $response;
        }

        // Mark as processing (prevents race conditions)
        $lockKey = "idempotency_lock:{$key}";
        if (! Cache::add($lockKey, true, 30)) {
            return response()->json([
                'error' => 'request_in_progress',
                'message' => 'A request with this idempotency key is already being processed.',
            ], 409);
        }

        try {
            $response = $next($request);

            // Skip caching if body > 1MB
            if (strlen($response->getContent()) > 1048576) {
                return $response;
            }

            // Only cache successful responses
            if ($response->isSuccessful()) {
                Cache::put($cacheKey, [
                    'body' => encrypt($response->getContent()),
                    'status' => $response->getStatusCode(),
                ], self::TTL);
            }

            return $response;
        } finally {
            Cache::forget($lockKey);
        }
    }
}
