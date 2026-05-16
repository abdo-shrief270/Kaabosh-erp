<?php

return [
    'driver' => env('ERROR_REPORTING_DRIVER', 'log'), // log, slack, sentry
    'slack_webhook_url' => env('SLACK_ERROR_WEBHOOK_URL'),
    'sentry_dsn' => env('SENTRY_DSN'),

    // Only report errors above this level
    'min_level' => env('ERROR_REPORTING_MIN_LEVEL', 'error'),

    // Throttle duplicate errors (same exception+file) for N seconds
    'throttle_seconds' => env('ERROR_REPORTING_THROTTLE', 300),
];
