<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the authenticated user account is active.
 */
class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->is_active) {
            return response()->json([
                'message' => 'Your account has been deactivated. Please contact your administrator.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
