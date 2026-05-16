<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Marks an endpoint as deprecated.
 *
 * Adds RFC 8594 / RFC 9745-style headers so API clients can detect the
 * deprecation without parsing bodies, and logs each hit to the
 * `api_deprecation` channel so we can track residual usage before
 * removing the endpoint.
 *
 * Usage:
 *   Route::middleware('deprecated:/admin/tenants,2026-07-17')
 *
 * First arg = path to the replacement (shown in the `Link` header as rel=successor-version).
 * Second arg (optional) = ISO date after which the endpoint may disappear.
 *   Defaults to 90 days from the first hit (baseline: request time).
 */
class Deprecated
{
    public function handle(Request $request, Closure $next, string $replacement = '', string $sunset = ''): Response
    {
        $response = $next($request);

        $sunsetDate = $sunset !== '' ? $sunset : now()->addDays(90)->toDateString();

        $response->headers->set('Deprecation', 'true');
        $response->headers->set('Sunset', $sunsetDate);

        if ($replacement !== '') {
            $response->headers->set(
                'Link',
                sprintf('<%s>; rel="successor-version"', $replacement),
            );
        }

        Log::channel(config('logging.deprecation_channel', 'stack'))->warning('deprecated_endpoint_hit', [
            'path' => $request->path(),
            'method' => $request->method(),
            'user_id' => $request->user()?->id,
            'tenant_id' => app('tenant.id'),
            'replacement' => $replacement,
            'sunset' => $sunsetDate,
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
        ]);

        return $response;
    }
}
