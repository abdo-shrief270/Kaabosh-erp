<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs mutating admin-panel requests (POST/PUT/PATCH/DELETE) to the Spatie
 * activity log under the `admin_panel` log channel.
 *
 * Sensitive fields (passwords, tokens, secrets) are redacted from logged
 * request parameters.
 */
class LogAdminActivity
{
    /**
     * HTTP verbs that mutate state and therefore must be audited.
     *
     * @var array<int, string>
     */
    private const MUTATING_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Keys whose values must never be persisted to the audit log.
     *
     * @var array<int, string>
     */
    private const REDACTED_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        '_token',
        'api_token',
        'access_token',
        'refresh_token',
        'secret',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! in_array($request->method(), self::MUTATING_METHODS, true)) {
            return $response;
        }

        $user = $request->user();

        if (! $user) {
            return $response;
        }

        $route = Route::current();
        $routeName = $route?->getName() ?? $route?->uri() ?? $request->path();

        activity('admin_panel')
            ->causedBy($user)
            ->withProperties([
                'route' => $routeName,
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'params' => $this->redact($request->except(['password', 'password_confirmation'])),
                'ip' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'status' => $response->getStatusCode(),
            ])
            ->log('admin.request');

        return $response;
    }

    /**
     * Recursively redact sensitive values from the payload.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function redact(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::REDACTED_KEYS, true)) {
                $data[$key] = '[REDACTED]';

                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->redact($value);
            }
        }

        return $data;
    }
}
