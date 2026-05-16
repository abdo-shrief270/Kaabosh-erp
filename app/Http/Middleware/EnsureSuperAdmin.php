<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the authenticated user is a super admin.
 * Used for platform-level routes (super admin panel).
 */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isSuperAdmin()) {
            return response()->json([
                'message' => 'Unauthorized. Super admin access required.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
