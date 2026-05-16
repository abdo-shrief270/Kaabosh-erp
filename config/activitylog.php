<?php

declare(strict_types=1);

/*
 * Spatie laravel-activitylog config. The defaults work fine in production;
 * we override `default_auth_driver` so the CauserResolver picks the right
 * guard when writing entries during test runs (where the harness uses
 * sanctum but doesn't always wire the default-null guard provider).
 */
return [
    'enabled' => env('ACTIVITY_LOGGER_ENABLED', true),
    'delete_records_older_than_days' => 365,
    'default_log_name' => 'default',
    'default_auth_driver' => 'web',
    'subject_returns_soft_deleted_models' => false,
    'activity_model' => \Spatie\Activitylog\Models\Activity::class,
    'table_name' => env('ACTIVITY_LOGGER_TABLE_NAME', 'activity_log'),
    'database_connection' => env('ACTIVITY_LOGGER_DB_CONNECTION'),
];
