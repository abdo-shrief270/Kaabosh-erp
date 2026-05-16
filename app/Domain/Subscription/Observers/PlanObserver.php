<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Observers;

use App\Domain\Subscription\Models\Plan;
use App\Domain\Subscription\Services\PlanFeatureCache;

class PlanObserver
{
    public function saved(Plan $plan): void
    {
        PlanFeatureCache::flush();
    }

    public function deleted(Plan $plan): void
    {
        PlanFeatureCache::flush();
    }

    public function restored(Plan $plan): void
    {
        PlanFeatureCache::flush();
    }
}
