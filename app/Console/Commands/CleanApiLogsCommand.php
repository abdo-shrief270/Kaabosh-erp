<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Shared\Models\ApiRequestLog;
use Illuminate\Console\Command;

class CleanApiLogsCommand extends Command
{
    protected $signature = 'api-logs:clean {--days= : Override retention days from config}';

    protected $description = 'Delete API request logs older than retention period';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('api.logging.retention_days', 30));

        $cutoff = now()->subDays($days);

        $deleted = ApiRequestLog::where('created_at', '<', $cutoff)->delete();

        $this->info("Deleted {$deleted} API log entries older than {$days} days.");

        return self::SUCCESS;
    }
}
