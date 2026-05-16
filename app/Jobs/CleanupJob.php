<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Shared\Models\ApiRequestLog;
use App\Domain\Webhook\Models\WebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Performs various cleanup tasks asynchronously:
 * - Old API logs
 * - Expired temporary files
 * - Old webhook deliveries
 */
class CleanupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct()
    {
        $this->onQueue('maintenance');
    }

    public function handle(): void
    {
        $this->cleanApiLogs();
        $this->cleanTempFiles();
        $this->cleanWebhookDeliveries();
    }

    private function cleanApiLogs(): void
    {
        $days = config('api.logging.retention_days', 30);
        $deleted = ApiRequestLog::where('created_at', '<', now()->subDays($days))->delete();
        if ($deleted > 0) {
            logger()->info("Cleaned {$deleted} API log entries.");
        }
    }

    private function cleanTempFiles(): void
    {
        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            return;
        }

        $cutoff = now()->subHours(24)->timestamp;
        $count = 0;

        foreach (glob($tempDir.'/*') as $file) {
            if (is_file($file) && filemtime($file) < $cutoff) {
                @unlink($file);
                $count++;
            }
        }

        if ($count > 0) {
            logger()->info("Cleaned {$count} temp files.");
        }
    }

    private function cleanWebhookDeliveries(): void
    {
        $deleted = WebhookDelivery::where('created_at', '<', now()->subDays(30))
            ->whereIn('status', ['success', 'failed'])
            ->delete();

        if ($deleted > 0) {
            logger()->info("Cleaned {$deleted} old webhook deliveries.");
        }
    }
}
