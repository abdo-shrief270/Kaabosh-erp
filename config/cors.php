<?php

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing (CORS) Configuration
|--------------------------------------------------------------------------
|
| In development, CORS_ALLOWED_ORIGINS may be set to "*" for convenience.
| In production, set explicit domains:
|   CORS_ALLOWED_ORIGINS="https://app.muhasebi.com,https://muhasebi.com"
|
| Empty default = fail-closed: no cross-origin requests are allowed unless
| CORS_ALLOWED_ORIGINS or CORS_ALLOWED_PATTERN is set. This is intentional —
| an open default would silently expose every install on first boot.
|
| CORS_SUPPORTS_CREDENTIALS=true MUST NOT be combined with the wildcard "*"
| origin; browsers reject the combination per spec. We trip an exception at
| boot if both are set so the misconfiguration surfaces loudly instead of
| breaking SPA auth at runtime.
*/

$allowedOrigins = array_values(array_filter(array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', '')))));
$supportsCredentials = (bool) env('CORS_SUPPORTS_CREDENTIALS', false);

if ($supportsCredentials && in_array('*', $allowedOrigins, true)) {
    throw new RuntimeException(
        'CORS misconfiguration: CORS_SUPPORTS_CREDENTIALS=true cannot be used with the wildcard "*" origin. '
        .'Set CORS_ALLOWED_ORIGINS to an explicit comma-separated list of domains.'
    );
}

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => $allowedOrigins,

    'allowed_origins_patterns' => array_values(array_filter([
        // Allow all subdomains of muhasebi.com in production
        env('CORS_ALLOWED_PATTERN', ''),
    ])),

    'allowed_headers' => [
        'Content-Type',
        'Accept',
        'Authorization',
        'X-Requested-With',
        'X-Tenant',
        'X-Request-Id',
        'Accept-Language',
        'X-Timezone',
        'X-Client-Version',
        // Mutation endpoints (documents upload, payments, invoices, …)
        // attach an Idempotency-Key header. Browsers preflight any custom
        // header, so it must appear here or the actual request gets blocked
        // after a successful OPTIONS response.
        'Idempotency-Key',
    ],

    'exposed_headers' => [
        'X-API-Version',
        'X-Request-Id',
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-API-Deprecation',
        'Sunset',
        'ETag',
    ],

    'max_age' => 7200, // 2 hours — reduces preflight requests

    'supports_credentials' => $supportsCredentials,

];
