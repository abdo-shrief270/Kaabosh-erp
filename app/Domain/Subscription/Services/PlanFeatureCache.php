<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Services;

use App\Domain\Subscription\Models\Plan;
use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Cache;

/**
 * Cached lookups for per-plan feature flags.
 *
 * Avoids re-reading the `plans.features` JSON column on every request.
 * Tagged under "plans" so the PlanObserver can flush the whole group
 * on save/delete. Falls back to a flat cache entry when the configured
 * store doesn't support tags.
 */
final class PlanFeatureCache
{
    private const CACHE_TTL = 600; // 10 minutes

    private const CACHE_TAG = 'plans';

    /**
     * Whether a plan grants access to the given feature key.
     */
    public static function has(int $planId, string $feature): bool
    {
        $key = self::key($planId, $feature);

        $resolver = static function () use ($planId, $feature): bool {
            $plan = Plan::query()->find($planId);

            return $plan instanceof Plan ? $plan->hasFeature($feature) : false;
        };

        if (self::supportsTags()) {
            return (bool) Cache::tags([self::CACHE_TAG])->remember($key, self::CACHE_TTL, $resolver);
        }

        return (bool) Cache::remember($key, self::CACHE_TTL, $resolver);
    }

    /**
     * Flush every cached plan feature lookup.
     */
    public static function flush(): void
    {
        if (self::supportsTags()) {
            Cache::tags([self::CACHE_TAG])->flush();
        }
        // For non-taggable stores, entries simply expire after TTL.
    }

    private static function key(int $planId, string $feature): string
    {
        return "plan_feature:{$planId}:{$feature}";
    }

    private static function supportsTags(): bool
    {
        return Cache::getStore() instanceof TaggableStore;
    }
}
