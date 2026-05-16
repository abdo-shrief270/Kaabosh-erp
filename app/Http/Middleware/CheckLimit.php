<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Subscription\Services\UsageService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Checks if the tenant is within plan limits for a specific resource.
 *
 * Usage: Route::middleware('limit:clients')
 */
class CheckLimit
{
    public function __construct(
        private readonly UsageService $usageService,
    ) {}

    public function handle(Request $request, Closure $next, string $resource): Response
    {
        if (! $this->usageService->checkLimit($resource)) {
            return response()->json([
                'message' => 'لقد تجاوزت الحد المسموح به في خطتك الحالية. يرجى ترقية اشتراكك.',
                'error' => 'limit_exceeded',
                'resource' => $resource,
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
