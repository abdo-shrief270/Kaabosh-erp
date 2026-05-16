<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces two-factor authentication for SuperAdmin users accessing the
 * Filament admin panel.
 *
 * The existing `Enforce2fa` middleware returns a JSON 403 which is
 * appropriate for the REST API but unusable for a browser panel. This
 * middleware instead redirects the SuperAdmin to the 2FA setup page and
 * allows them to stay on the setup / logout routes without a loop.
 */
class EnforceSuperAdmin2fa
{
    /**
     * Path fragments that must be reachable without a 2FA token so the
     * user can enroll or log out without being trapped in a redirect loop.
     *
     * @var array<int, string>
     */
    private const ALLOWED_FRAGMENTS = [
        '2fa',
        'logout',
        'login',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isSuperAdmin()) {
            return $next($request);
        }

        if ((bool) ($user->two_factor_enabled ?? false)) {
            return $next($request);
        }

        $path = '/'.ltrim($request->path(), '/');

        foreach (self::ALLOWED_FRAGMENTS as $fragment) {
            if (str_contains($path, $fragment)) {
                return $next($request);
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Two-factor authentication is required for SuperAdmin accounts.',
                'message_ar' => 'المصادقة الثنائية مطلوبة لحسابات المشرف الأعلى.',
                'code' => '2fa_required',
                'setup_url' => '/2fa/setup',
            ], Response::HTTP_FORBIDDEN);
        }

        return redirect('/2fa/setup')
            ->with('warning', 'Please enable two-factor authentication to continue.');
    }
}
