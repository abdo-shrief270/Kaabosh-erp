<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class ValidateEnvironmentCommand extends Command
{
    protected $signature = 'env:validate {--strict : Fail on warnings too}';

    protected $description = 'Validate environment configuration and service connectivity';

    private const REQUIRED_VARS = [
        'APP_KEY',
        'APP_URL',
        'DB_HOST',
        'DB_DATABASE',
        'DB_USERNAME',
    ];

    private const PRODUCTION_VARS = [
        'CORS_ALLOWED_ORIGINS',
        'ADMIN_IP_WHITELIST',
        'SANCTUM_EXPIRATION',
        'REDIS_HOST',
    ];

    public function handle(): int
    {
        $errors = 0;
        $warnings = 0;

        $this->info('Environment Validation');
        $this->line('');

        // 1. Required vars
        $this->comment('Checking environment variables...');
        foreach (self::REQUIRED_VARS as $var) {
            if (empty(env($var))) {
                $this->error("  ✗ {$var} is missing");
                $errors++;
            } else {
                $this->line("  ✓ {$var}");
            }
        }

        if (app()->isProduction()) {
            foreach (self::PRODUCTION_VARS as $var) {
                if (empty(env($var))) {
                    $this->warn("  ⚠ {$var} recommended for production");
                    $warnings++;
                } else {
                    $this->line("  ✓ {$var}");
                }
            }

            if (env('APP_DEBUG')) {
                $this->error('  ✗ APP_DEBUG must be false in production');
                $errors++;
            }
        }

        // 2. Database connectivity
        $this->line('');
        $this->comment('Checking services...');
        try {
            DB::connection()->getPdo();
            $this->line('  ✓ Database');
        } catch (\Throwable $e) {
            $this->error("  ✗ Database: {$e->getMessage()}");
            $errors++;
        }

        // 3. Redis
        try {
            Redis::ping();
            $this->line('  ✓ Redis');
        } catch (\Throwable $e) {
            $this->warn("  ⚠ Redis: {$e->getMessage()}");
            $warnings++;
        }

        // 4. Storage
        try {
            $testFile = 'health_check_'.uniqid().'.tmp';
            Storage::put($testFile, 'ok');
            Storage::delete($testFile);
            $this->line('  ✓ Storage');
        } catch (\Throwable $e) {
            $this->error("  ✗ Storage: {$e->getMessage()}");
            $errors++;
        }

        // 5. Pending migrations
        try {
            $pending = count(app('migrator')->pendingMigrations(
                app('migrator')->paths(),
                app('migration.repository')->getRan()
            ));
            if ($pending > 0) {
                $this->warn("  ⚠ {$pending} pending migration(s)");
                $warnings++;
            } else {
                $this->line('  ✓ Migrations up to date');
            }
        } catch (\Throwable) {
            $this->warn('  ⚠ Could not check migrations');
            $warnings++;
        }

        // Summary
        $this->line('');
        if ($errors > 0) {
            $this->error("Failed: {$errors} error(s), {$warnings} warning(s)");

            return 1;
        }

        if ($warnings > 0 && $this->option('strict')) {
            $this->warn("Strict mode: {$warnings} warning(s) treated as errors");

            return 1;
        }

        $this->info("Passed: 0 errors, {$warnings} warning(s)");

        return 0;
    }
}
