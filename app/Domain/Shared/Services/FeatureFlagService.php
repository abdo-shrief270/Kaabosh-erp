<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Models\FeatureFlag;
use Illuminate\Support\Facades\Cache;

/**
 * Feature flag service for toggling features per tenant/plan.
 *
 * Usage:
 *   FeatureFlagService::isEnabled('client_portal', tenantId: 5, planId: 2)
 *   FeatureFlagService::check('eta_integration') // uses current tenant context
 */
class FeatureFlagService
{
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Check if a feature is enabled for a specific tenant.
     */
    public static function isEnabled(string $key, ?int $tenantId = null, ?int $planId = null): bool
    {
        $tenantId = $tenantId ?? app('tenant.id');
        if (! $tenantId) {
            return false;
        }

        $flag = self::getFlag($key);
        if (! $flag) {
            return false;
        }

        return $flag->isEnabledFor($tenantId, $planId);
    }

    /**
     * Check using current tenant context (convenience method).
     */
    public static function check(string $key): bool
    {
        $tenantId = app('tenant.id');
        $planId = null;

        // Try to resolve plan from current subscription
        $tenant = app()->bound('tenant') ? app('tenant') : null;
        if ($tenant) {
            $planId = $tenant->subscriptions()
                ->where('status', 'active')
                ->orWhere('status', 'trial')
                ->value('plan_id');
        }

        return self::isEnabled($key, $tenantId, $planId);
    }

    /**
     * Get all features enabled for a tenant.
     *
     * Resolution under Model B:
     *   1. Start with the firm tier's manifest (FirmSubscriptionTier::features)
     *   2. Layer SuperAdmin firm-level overrides on top
     *      (enabled_for_firms / disabled_for_firms on feature_flags rows)
     *
     * The legacy per-tenant override columns on feature_flags are intentionally
     * ignored — features are firm-wide, not per-client.
     *
     * @return array<string, bool>
     */
    public static function getAllForTenant(int $tenantId, ?int $planId = null): array
    {
        return Cache::remember("feature_flags:tenant:{$tenantId}", self::CACHE_TTL, function () use ($tenantId) {
            $tenant = \App\Domain\Tenant\Models\Tenant::find($tenantId);
            if (! $tenant?->firm_id) return [];

            $firm = \App\Domain\Firm\Models\Firm::find($tenant->firm_id);

            // Source of truth = firm's active Subscription → Plan.features.
            // Falls back to the deprecated firms.subscription_tier column
            // only for firms that haven't been backfilled.
            $subscription = \App\Domain\Subscription\Models\Subscription::activeForFirm($tenant->firm_id);
            $planFeatures = $subscription?->plan?->features;

            if (! is_array($planFeatures)) {
                $tier = $firm?->subscription_tier
                    instanceof \App\Domain\Shared\Enums\FirmSubscriptionTier
                    ? $firm->subscription_tier
                    : null;
                if (! $tier) return [];
                $planFeatures = $tier->features();
            }

            // Layer 1 — plan manifest. Catalog features default to their
            // plan-membership value (true if listed, false otherwise).
            $catalog     = array_keys((array) config('features.catalog', []));
            $enabledKeys = array_flip($planFeatures);

            $result = [];
            foreach ($catalog as $key) {
                $result[$key] = array_key_exists($key, $enabledKeys);
            }

            // Layer 2 — SuperAdmin firm-level overrides. Flag rows that opine
            // for this firm force the resolved value regardless of tier.
            FeatureFlag::query()
                ->where(function ($q): void {
                    $q->whereNotNull('enabled_for_firms')
                      ->orWhereNotNull('disabled_for_firms');
                })
                ->get()
                ->each(function (FeatureFlag $flag) use ($firm, &$result): void {
                    if (! array_key_exists($flag->key, $result)) return;
                    $override = $flag->firmOverride($firm->id);
                    if ($override !== null) {
                        $result[$flag->key] = $override;
                    }
                });

            return $result;
        });
    }

    /**
     * Get a single flag (cached).
     */
    private static function getFlag(string $key): ?FeatureFlag
    {
        return Cache::remember("feature_flag:{$key}", self::CACHE_TTL, function () use ($key) {
            return FeatureFlag::where('key', $key)->first();
        });
    }

    /**
     * Clear all feature flag caches (call after admin updates).
     *
     * Invoked automatically by FeatureFlagObserver on save/delete and
     * may also be called manually from console/admin actions.
     */
    public static function clearCache(): void
    {
        $flags = FeatureFlag::all();
        foreach ($flags as $flag) {
            Cache::forget("feature_flag:{$flag->key}");
        }
        // Per-tenant rollups expire via TTL; most cache stores don't
        // support pattern deletion so we rely on the 5-minute window.
    }
}
