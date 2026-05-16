<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the admin panel locale. Defaults to English (LTR); individual users can
 * switch themselves to Arabic (RTL) via `users.locale` on their profile.
 * Filament ships chrome translations for both locales and flips HTML direction
 * automatically via its layout/direction translation key.
 */
class SetAdminLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $locale = $user?->locale ?? 'en';

        if (in_array($locale, ['ar', 'en'], true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
