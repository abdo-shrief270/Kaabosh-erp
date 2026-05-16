<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Tenant\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ERP single-company-per-account: each authenticated user belongs to
 * exactly one Company (legacy `tenant_id` column). This middleware just
 * binds the user's own company to the container so the existing
 * `BelongsToTenant` global scope keeps working. No X-Tenant header,
 * no subdomain routing, no tenant switching.
 */
class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->tenant_id) {
            return response()->json(['message' => 'Company not resolved.'], Response::HTTP_NOT_FOUND);
        }

        $tenant = Tenant::query()->find($user->tenant_id);
        if (! $tenant) {
            return response()->json(['message' => 'Company not found.'], Response::HTTP_NOT_FOUND);
        }

        if (method_exists($tenant, 'isAccessible') && ! $tenant->isAccessible()) {
            return response()->json([
                'message' => 'Company account is not accessible.',
            ], Response::HTTP_FORBIDDEN);
        }

        app()->instance('tenant', $tenant);
        app()->instance('tenant.id', $tenant->id);

        return $next($request);
    }
}
