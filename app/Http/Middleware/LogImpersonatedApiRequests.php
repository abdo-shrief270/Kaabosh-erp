<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Flags and logs API requests that are made with an impersonation token.
 */
class LogImpersonatedApiRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken && str_starts_with((string) $token->name, 'impersonation-')) {
            $response->headers->set('X-Impersonation', 'true');

            Log::channel('stack')->info('Impersonated API request', [
                'token_name' => $token->name,
                'path' => '/'.ltrim($request->path(), '/'),
                'method' => $request->method(),
                'user_id' => $request->user()?->id,
            ]);
        }

        return $response;
    }
}
