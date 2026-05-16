<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Shared\Models\ApiUsageMeter;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class FlushUsageBuffersCommand extends Command
{
    protected $signature = 'usage:flush-buffers';

    protected $description = 'Flush any remaining API usage buffer counts to the database';

    public function handle(): int
    {
        $today = now()->toDateString();
        $flushed = 0;

        Tenant::select('id')->chunk(100, function ($tenants) use ($today, &$flushed) {
            foreach ($tenants as $tenant) {
                $key = "usage_buffer:{$tenant->id}:{$today}";
                $remaining = (int) Cache::get($key, 0);

                if ($remaining > 0) {
                    ApiUsageMeter::incrementFor($tenant->id, 'api_calls', $remaining);
                    Cache::forget($key);
                    $flushed++;
                }
            }
        });

        if ($flushed > 0) {
            $this->info("Flushed usage buffers for {$flushed} tenant(s).");
        }

        return self::SUCCESS;
    }
}
