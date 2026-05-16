<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private const SUPPORTED_LOCALES = ['ar', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        App::setLocale($locale);

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        // 1. Accept-Language header
        $headerLocale = $request->getPreferredLanguage(self::SUPPORTED_LOCALES);

        if ($headerLocale && in_array($headerLocale, self::SUPPORTED_LOCALES, true)) {
            return $headerLocale;
        }

        // 2. User preference
        $user = $request->user();

        if ($user?->locale && in_array($user->locale, self::SUPPORTED_LOCALES, true)) {
            return $user->locale;
        }

        // 3. Tenant settings
        $tenant = app()->bound('tenant') ? app('tenant') : null;
        $settings = $tenant?->settings ?? [];
        $request->attributes->set('tenant_settings', $settings);

        if ($tenant) {
            $tenantLocale = is_array($settings) ? ($settings['locale'] ?? null) : null;

            if ($tenantLocale && in_array($tenantLocale, self::SUPPORTED_LOCALES, true)) {
                return $tenantLocale;
            }
        }

        // 4. Default
        return 'ar';
    }
}
