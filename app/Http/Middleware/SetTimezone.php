<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTimezone
{
    private static ?array $validTimezones = null;

    private function isValidTimezone(string $tz): bool
    {
        if (self::$validTimezones === null) {
            self::$validTimezones = array_flip(\DateTimeZone::listIdentifiers());
        }

        return isset(self::$validTimezones[$tz]);
    }

    public function handle(Request $request, Closure $next): Response
    {
        $timezone = $this->resolveTimezone($request);

        date_default_timezone_set($timezone);
        app()->instance('timezone', $timezone);

        return $next($request);
    }

    public function terminate(): void
    {
        date_default_timezone_set('UTC');
    }

    private function resolveTimezone(Request $request): string
    {
        $user = $request->user();

        if ($user?->timezone && $this->isValidTimezone($user->timezone)) {
            return $user->timezone;
        }

        $settings = $request->attributes->get('tenant_settings');
        if ($settings === null) {
            $tenant = app()->bound('tenant') ? app('tenant') : null;
            $settings = $tenant?->settings ?? [];
        }

        $tz = is_array($settings) ? ($settings['timezone'] ?? null) : null;

        if ($tz && $this->isValidTimezone($tz)) {
            return $tz;
        }

        return 'Africa/Cairo';
    }
}
