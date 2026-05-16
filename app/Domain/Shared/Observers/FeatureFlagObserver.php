<?php

declare(strict_types=1);

namespace App\Domain\Shared\Observers;

use App\Domain\Shared\Models\FeatureFlag;
use App\Domain\Shared\Services\FeatureFlagService;

class FeatureFlagObserver
{
    public function saved(FeatureFlag $flag): void
    {
        FeatureFlagService::clearCache();
    }

    public function deleted(FeatureFlag $flag): void
    {
        FeatureFlagService::clearCache();
    }
}
