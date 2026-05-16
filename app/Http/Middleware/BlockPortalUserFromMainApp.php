<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Shared\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hard fence between the client portal and the main dashboard.
 *
 * Client-role users (the businesses whose books a firm manages) can only use
 * the portal — they must never reach firm-staff endpoints even if they have
 * a valid Sanctum token. The mirror exists in ClientPortalMiddleware which
 * blocks non-client users from /portal routes.
 *
 * Skips:
 *  - unauthenticated requests (auth middleware will handle them first)
 *  - SuperAdmin (always allowed for support)
 *  - non-client users (the typical firm-staff case)
 */
class BlockPortalUserFromMainApp
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) return $next($request);
        if ($user->isSuperAdmin()) return $next($request);

        if ($user->role === UserRole::Client) {
            return response()->json([
                'message' => 'Client portal accounts cannot access the firm dashboard. Use /portal instead.',
                'code'    => 'portal_user_blocked',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
