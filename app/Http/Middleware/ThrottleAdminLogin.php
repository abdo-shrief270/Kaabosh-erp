<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/**
 * Applies strict rate limiting to the Filament admin login POST endpoint.
 *
 * Only triggers on POST requests to a path ending in `/login` under `/admin`,
 * so it can be registered globally on the admin middleware stack without
 * throttling regular page loads.
 *
 * Limit: 5 attempts per minute per IP (keyed by `admin-login` limiter).
 */
class ThrottleAdminLogin
{
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    public function __construct(private readonly RateLimiter $limiter) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldThrottle($request)) {
            return $next($request);
        }

        $key = 'admin-login:'.$request->ip();

        if ($this->limiter->tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $retryAfter = $this->limiter->availableIn($key);

            throw new TooManyRequestsHttpException(
                retryAfter: $retryAfter,
                message: 'Too many login attempts. Please try again in '.$retryAfter.' seconds.',
            );
        }

        $this->limiter->hit($key, self::DECAY_SECONDS);

        return $next($request);
    }

    private function shouldThrottle(Request $request): bool
    {
        if ($request->method() !== 'POST') {
            return false;
        }

        $path = '/'.ltrim($request->path(), '/');

        return str_starts_with($path, '/admin')
            && (str_ends_with($path, '/login') || str_contains($path, '/login'));
    }
}
