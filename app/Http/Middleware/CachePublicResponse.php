<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds Cache-Control headers to public (unauthenticated) API responses.
 * Used on landing, blog listing, plans, pages endpoints.
 */
class CachePublicResponse
{
    public function handle(Request $request, Closure $next, int $maxAge = 300): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET') && $response->isSuccessful()) {
            $response->headers->set('Cache-Control', "public, max-age={$maxAge}, s-maxage=".($maxAge * 2));
            $response->headers->set('Vary', 'Accept-Language, Accept');

            // ETag for conditional requests
            $etag = md5($response->getContent());
            $response->headers->set('ETag', "\"{$etag}\"");

            if ($request->headers->get('If-None-Match') === "\"{$etag}\"") {
                $response->setStatusCode(304);
                $response->setContent('');
            }
        }

        return $response;
    }
}
