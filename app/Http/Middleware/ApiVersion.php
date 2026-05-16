<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds API versioning headers to every response.
 *
 * Headers returned:
 * - X-API-Version: Current API version (e.g. "1.0")
 * - X-API-Deprecation: Warning when using deprecated endpoints
 * - X-Request-Id: Unique request identifier for debugging
 */
class ApiVersion
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $version = config('api.version', '1.0');
        $response->headers->set('X-API-Version', $version);
        $response->headers->set('X-Request-Id', $this->getRequestId($request));

        // Check if route is deprecated
        $route = $request->route();
        if ($route) {
            $deprecated = $route->defaults['deprecated'] ?? false;
            if ($deprecated) {
                $sunset = $route->defaults['sunset'] ?? null;
                $replacement = $route->defaults['replacement'] ?? null;

                $warning = 'This endpoint is deprecated.';
                if ($replacement) {
                    $warning .= " Use {$replacement} instead.";
                }
                if ($sunset) {
                    $warning .= " Sunset date: {$sunset}.";
                    $response->headers->set('Sunset', $sunset);
                }

                $response->headers->set('X-API-Deprecation', $warning);
                $response->headers->set('Deprecation', 'true');
            }
        }

        return $response;
    }

    private function getRequestId(Request $request): string
    {
        // Use incoming request ID if provided (from load balancer), otherwise generate one
        return $request->header('X-Request-Id') ?: bin2hex(random_bytes(12));
    }
}
