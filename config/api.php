<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | The current API version returned in the X-API-Version header.
    | Increment this when breaking changes are introduced.
    |
    */
    'version' => env('API_VERSION', '1.0'),

    /*
    |--------------------------------------------------------------------------
    | API Latest Version
    |--------------------------------------------------------------------------
    |
    | The latest available API version. Used for migration guidance.
    |
    */
    'latest_version' => env('API_LATEST_VERSION', '1.0'),

    /*
    |--------------------------------------------------------------------------
    | Request Logging
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => env('API_REQUEST_LOGGING', false),
        'slow_threshold_ms' => env('API_SLOW_THRESHOLD', 1000),
        'exclude_paths' => [
            'api/v1/health',
            'api/v1/blog/rss',
        ],
        'exclude_methods' => [
            'OPTIONS',
        ],
        // Max days to keep logs (cleaned by scheduled command)
        'retention_days' => env('API_LOG_RETENTION_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    */
    'webhooks' => [
        'timeout' => env('WEBHOOK_TIMEOUT', 10),
        'max_retries' => env('WEBHOOK_MAX_RETRIES', 3),
        'retry_delay_minutes' => [1, 5, 30], // Exponential backoff
        'signing_secret_header' => 'X-Muhasebi-Signature',
    ],
];
