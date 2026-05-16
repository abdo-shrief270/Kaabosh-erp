<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces two-factor authentication for admin users.
 * Returns 403 with setup URL if 2FA is not enabled.
 *
 * 2FA setup routes (/2fa/enable, /2fa/status) are registered outside
 * this middleware's scope so admins can always configure 2FA first.
 */
class Enforce2fa
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $requiresEnforcement = $user->isSuperAdmin() || $user->isAdmin();

        if ($requiresEnforcement && ! $user->two_factor_enabled) {
            return response()->json([
                'message' => 'Two-factor authentication is required for admin accounts. Please enable 2FA to continue.',
                'message_ar' => 'المصادقة الثنائية مطلوبة لحسابات المدراء. يرجى تفعيل المصادقة الثنائية للمتابعة.',
                'code' => '2fa_required',
                'setup_url' => '/v1/2fa/enable',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
