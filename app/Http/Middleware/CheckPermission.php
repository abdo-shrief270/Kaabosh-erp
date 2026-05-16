<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (! Gate::allows($permission)) {
            return response()->json([
                'message' => 'Insufficient permissions.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
