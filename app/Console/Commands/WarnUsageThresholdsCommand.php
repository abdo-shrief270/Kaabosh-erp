<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Subscription\Services\UsageWarningService;
use Illuminate\Console\Command;

class WarnUsageThresholdsCommand extends Command
{
    protected $signature = 'subscriptions:warn-usage';

    protected $description = 'Email tenant owners when usage crosses 80% or 100% of any plan limit (idempotent per day).';

    public function handle(UsageWarningService $service): int
    {
        $sent = $service->sweep();

        $this->info("Dispatched {$sent} usage warning(s).");

        return self::SUCCESS;
    }
}
