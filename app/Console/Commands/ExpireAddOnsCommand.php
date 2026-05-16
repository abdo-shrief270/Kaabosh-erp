<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Subscription\Services\AddOnService;
use Illuminate\Console\Command;

class ExpireAddOnsCommand extends Command
{
    protected $signature = 'subscriptions:expire-add-ons';

    protected $description = 'Mark subscription add-ons as cancelled/expired when they pass their period_end or expires_at.';

    public function handle(AddOnService $service): int
    {
        $count = $service->expireDue();

        $this->info("Touched {$count} add-on row(s).");

        return self::SUCCESS;
    }
}
