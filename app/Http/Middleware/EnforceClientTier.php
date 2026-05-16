<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Firm\Services\ClientTierService;
use App\Domain\Tenant\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks transaction-creating requests on client-tenants that have hit their
 * monthly tier limit. Only applied to invoice/bill store routes — read-only
 * and edit endpoints stay open so staff can finish in-flight work.
 *
 * Firm-books tenants are never blocked here — they're covered by the firm's
 * base subscription, not the per-client tier ladder.
 */
class EnforceClientTier
{
    public function __construct(
        private readonly ClientTierService $tierService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Only enforce on creates — reads, edits, and deletes always pass so
        // staff can finish in-flight work and clean up after hitting the cap.
        if ($request->method() !== 'POST') {
            return $next($request);
        }

        if (! app()->bound('tenant')) {
            return $next($request);
        }

        /** @var Tenant $tenant */
        $tenant = app('tenant');
        if (! $tenant->isClientBooks()) {
            return $next($request);
        }

        if ($this->tierService->isAtLimit($tenant)) {
            $usage = $this->tierService->usage($tenant);
            return response()->json([
                'message' => 'Client tier monthly limit reached. Upgrade the tier to continue posting.',
                'code'    => 'client_tier_limit_reached',
                'usage'   => $usage,
            ], Response::HTTP_CONFLICT);
        }

        return $next($request);
    }
}
