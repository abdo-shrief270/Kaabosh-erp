<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Circuit breaker pattern for external API calls.
 *
 * States:
 * - CLOSED (normal): Requests pass through. Failures counted.
 * - OPEN (failing): Requests immediately fail without calling the API.
 * - HALF_OPEN (recovering): One test request allowed. Success → CLOSED, Failure → OPEN.
 *
 * Usage:
 *   $result = CircuitBreaker::call('paymob', function () {
 *       return Http::post('https://accept.paymob.com/api/...');
 *   });
 */
class CircuitBreaker
{
    private const STATE_CLOSED = 'closed';

    private const STATE_OPEN = 'open';

    private const STATE_HALF_OPEN = 'half_open';

    /**
     * Execute a callable with circuit breaker protection.
     *
     * @param  string  $service  Service name (e.g., 'paymob', 'fawry', 'beon_chat')
     * @param  callable  $callback  The external API call
     * @param  callable|null  $fallback  Called when circuit is open
     * @param  int  $failureThreshold  Failures before opening circuit (default: 5)
     * @param  int  $recoveryTimeout  Seconds before trying half-open (default: 60)
     * @param  int  $successThreshold  Successes in half-open before closing (default: 2)
     *
     * @throws \Throwable Re-throws if no fallback provided and circuit is open
     */
    public static function call(
        string $service,
        callable $callback,
        ?callable $fallback = null,
        int $failureThreshold = 5,
        int $recoveryTimeout = 60,
        int $successThreshold = 2,
    ): mixed {
        $state = self::getState($service);

        // OPEN: reject immediately
        if ($state === self::STATE_OPEN) {
            $openedAt = (int) Cache::get("circuit:{$service}:opened_at", 0);
            $elapsed = time() - $openedAt;

            if ($elapsed < $recoveryTimeout) {
                Log::warning("Circuit breaker OPEN for {$service}. Rejecting request.", [
                    'recovery_in' => $recoveryTimeout - $elapsed,
                ]);

                if ($fallback) {
                    return $fallback();
                }

                throw new \RuntimeException("Service '{$service}' is temporarily unavailable (circuit open).");
            }

            // Recovery timeout passed → move to half-open
            self::setState($service, self::STATE_HALF_OPEN);
            $state = self::STATE_HALF_OPEN;
        }

        // CLOSED or HALF_OPEN: try the call
        try {
            $result = $callback();

            // Success
            if ($state === self::STATE_HALF_OPEN) {
                $successes = (int) Cache::increment("circuit:{$service}:half_open_successes");
                if ($successes >= $successThreshold) {
                    self::reset($service);
                    Log::info("Circuit breaker CLOSED for {$service}. Service recovered.");
                }
            } else {
                // Reset failure count on success in closed state
                Cache::forget("circuit:{$service}:failures");
            }

            return $result;
        } catch (\Throwable $e) {
            // Failure
            $failures = (int) Cache::increment("circuit:{$service}:failures");

            if ($state === self::STATE_HALF_OPEN || $failures >= $failureThreshold) {
                self::setState($service, self::STATE_OPEN);
                Cache::put("circuit:{$service}:opened_at", time(), 3600);
                Cache::forget("circuit:{$service}:half_open_successes");

                Log::error("Circuit breaker OPENED for {$service} after {$failures} failures.", [
                    'error' => $e->getMessage(),
                    'recovery_timeout' => $recoveryTimeout,
                ]);
            }

            if ($fallback) {
                return $fallback();
            }

            throw $e;
        }
    }

    /**
     * Get current circuit state for a service.
     */
    public static function getState(string $service): string
    {
        return Cache::get("circuit:{$service}:state", self::STATE_CLOSED);
    }

    /**
     * Get status info for all monitored services (for admin dashboard).
     */
    public static function getAllStatuses(): array
    {
        $services = ['paymob', 'fawry', 'beon_chat', 'google'];
        $statuses = [];

        foreach ($services as $service) {
            $state = self::getState($service);
            $statuses[$service] = [
                'state' => $state,
                'failures' => (int) Cache::get("circuit:{$service}:failures", 0),
                'opened_at' => $state === self::STATE_OPEN
                    ? date('c', (int) Cache::get("circuit:{$service}:opened_at", 0))
                    : null,
            ];
        }

        return $statuses;
    }

    /**
     * Manually reset a circuit (admin action).
     */
    public static function reset(string $service): void
    {
        Cache::forget("circuit:{$service}:state");
        Cache::forget("circuit:{$service}:failures");
        Cache::forget("circuit:{$service}:opened_at");
        Cache::forget("circuit:{$service}:half_open_successes");
    }

    private static function setState(string $service, string $state): void
    {
        Cache::put("circuit:{$service}:state", $state, 3600);
    }
}
