<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prevents duplicate POST/PUT/PATCH/DELETE requests within a short window.
 * Uses a hash of: user_id + method + path + body to detect duplicates.
 *
 * Unlike IdempotencyKey (client-provided), this is automatic server-side protection.
 * Window is configurable (default: 5 seconds).
 */
class PreventDuplicateRequests
{
    public function handle(Request $request, Closure $next, int $windowSeconds = 5): Response
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            return $next($request);
        }

        $fingerprint = $this->buildFingerprint($request);
        $cacheKey = "dedup:{$fingerprint}";

        if (Cache::has($cacheKey)) {
            return response()->json([
                'error' => 'duplicate_request',
                'message' => 'This request was already submitted. Please wait a moment.',
            ], 429);
        }

        // Mark this request as in-flight
        Cache::put($cacheKey, true, $windowSeconds);

        return $next($request);
    }

    private function buildFingerprint(Request $request): string
    {
        $parts = [
            $request->user()?->id ?? $request->ip(),
            $request->method(),
            $request->path(),
            md5(json_encode($request->except(['_token', '_timestamp'])) ?: ''),
        ];

        return md5(implode('|', $parts));
    }
}
