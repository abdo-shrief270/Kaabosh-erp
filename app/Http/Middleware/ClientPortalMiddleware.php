<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Client\Models\Client;
use App\Domain\Shared\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClientPortalMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== UserRole::Client) {
            return response()->json([
                'message' => 'Access restricted to client portal users.',
            ], Response::HTTP_FORBIDDEN);
        }

        if (! $user->client_id) {
            return response()->json([
                'message' => 'No client account linked to this user.',
            ], Response::HTTP_FORBIDDEN);
        }

        $client = Client::find($user->client_id);

        if (! $client || ! $client->is_active) {
            return response()->json([
                'message' => 'Client account is not active.',
            ], Response::HTTP_FORBIDDEN);
        }

        app()->instance('portal.client', $client);

        return $next($request);
    }
}
