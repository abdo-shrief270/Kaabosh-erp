<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts super admin access to whitelisted IP addresses.
 * Configurable via ADMIN_IP_WHITELIST env var (comma-separated).
 * If empty or not set, all IPs are allowed (development mode).
 */
class AdminIpWhitelist
{
    public function handle(Request $request, Closure $next): Response
    {
        $whitelist = config('auth.admin_ip_whitelist', '');

        if (app()->isProduction() && empty($whitelist)) {
            abort(503, 'Admin IP whitelist not configured.');
        }

        // If no whitelist configured, allow all (development)
        if (empty($whitelist)) {
            return $next($request);
        }

        $allowedIps = array_map('trim', explode(',', $whitelist));
        $clientIp = $request->ip();

        // Support CIDR ranges
        foreach ($allowedIps as $allowed) {
            if ($this->ipInRange($clientIp, $allowed)) {
                return $next($request);
            }
        }

        return response()->json([
            'message' => 'Access denied. Your IP address is not whitelisted.',
        ], Response::HTTP_FORBIDDEN);
    }

    /**
     * Check if an IP is in a CIDR range or exact match.
     */
    private function ipInRange(string $ip, string $range): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        if (! str_contains($range, '/')) {
            return $ip === $range;
        }

        // CIDR matching is IPv4-only; IPv6 addresses can only match exactly (above)
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        [$subnet, $mask] = explode('/', $range, 2);
        $mask = (int) $mask;

        if ($mask < 0 || $mask > 32 || ! filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) ===
               (ip2long($subnet) & ~((1 << (32 - $mask)) - 1));
    }
}
