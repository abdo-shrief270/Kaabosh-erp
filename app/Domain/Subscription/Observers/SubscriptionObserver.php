<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Observers;

use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Services\PlanFeatureCache;
use Illuminate\Support\Facades\Log;

class SubscriptionObserver
{
    public function saved(Subscription $subscription): void
    {
        PlanFeatureCache::flush();

        Log::info('subscription.changed', [
            'subscription_id' => $subscription->id,
            'tenant_id' => $subscription->tenant_id,
            'plan_id' => $subscription->plan_id,
            'status' => $subscription->status?->value ?? (string) $subscription->status,
            'dirty' => array_keys($subscription->getChanges()),
        ]);
    }

    public function deleted(Subscription $subscription): void
    {
        PlanFeatureCache::flush();

        Log::info('subscription.changed', [
            'event' => 'deleted',
            'subscription_id' => $subscription->id,
            'tenant_id' => $subscription->tenant_id,
        ]);
    }
}
