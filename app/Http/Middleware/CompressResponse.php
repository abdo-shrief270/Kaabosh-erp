<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Compresses JSON responses with gzip when:
 * - Client accepts gzip encoding
 * - Response is JSON content type
 * - Response body is larger than threshold (1KB)
 *
 * Reduces bandwidth usage by ~70-80% for typical JSON API responses.
 */
class CompressResponse
{
    private const MIN_SIZE = 1024; // 1KB minimum to bother compressing

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldCompress($request, $response)) {
            return $response;
        }

        $content = $response->getContent();
        if ($content === false || strlen($content) < self::MIN_SIZE) {
            return $response;
        }

        $compressed = gzencode($content, 6); // Level 6 = good balance of speed vs ratio
        if ($compressed === false) {
            return $response;
        }

        $response->setContent($compressed);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Content-Length', (string) strlen($compressed));
        $response->headers->remove('Transfer-Encoding');

        // Vary header for proper caching
        $vary = $response->headers->get('Vary', '');
        if (! str_contains($vary, 'Accept-Encoding')) {
            $response->headers->set('Vary', $vary ? "{$vary}, Accept-Encoding" : 'Accept-Encoding');
        }

        return $response;
    }

    private function shouldCompress(Request $request, Response $response): bool
    {
        // Only compress if client accepts gzip
        if (! str_contains($request->header('Accept-Encoding', ''), 'gzip')) {
            return false;
        }

        // Only compress successful responses
        if (! $response->isSuccessful()) {
            return false;
        }

        // Only compress JSON responses
        $contentType = $response->headers->get('Content-Type', '');
        if (! str_contains($contentType, 'json') && ! str_contains($contentType, 'xml')) {
            return false;
        }

        // Skip if already encoded
        if ($response->headers->has('Content-Encoding')) {
            return false;
        }

        return true;
    }
}
