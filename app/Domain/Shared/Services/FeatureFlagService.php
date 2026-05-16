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
            // Single-company: features resolve from the tenant's own active
            // subscription → Plan.features, then per-tenant/global FeatureFlag
            // overrides layered on top. (The muhasebi firm-tier path was
            // removed — kaabosh has no firms.)
            $subscription = \App\Domain\Subscription\Models\Subscription::query()
                ->where('tenant_id', $tenantId)
                ->active()
                ->with('plan:id,slug,features')
                ->first();

            $planId       = $subscription?->plan?->id;
            $planFeatures = $subscription?->plan?->features;
            $planFeatures = is_array($planFeatures) ? $planFeatures : [];

            // Layer 1 — plan manifest. Catalog features default to their
            // plan-membership value (true if bundled, false otherwise).
            // Plan.features is canonically a {slug => bool} map (PlanSeeder
            // / Plan factory), but tolerate a legacy [slug, ...] list too.
            $catalog = array_keys((array) config('features.catalog', []));

            $enabledKeys = [];
            foreach ($planFeatures as $k => $v) {
                if (is_string($k)) {
                    if ($v) {
                        $enabledKeys[$k] = true; // map shape {slug: bool}
                    }
                } elseif (is_string($v)) {
                    $enabledKeys[$v] = true;     // list shape [slug, ...]
                }
            }

            $result = [];
            foreach ($catalog as $key) {
                $result[$key] = array_key_exists($key, $enabledKeys);
            }

            // Layer 2 — per-tenant + global FeatureFlag overrides. A flag
            // overlays only when it carries an explicit opinion (global,
            // per-tenant, or per-plan), so the plan bundle stays
            // authoritative by default. Non-catalog flags that opine are
            // still surfaced so the SPA can gate on them.
            FeatureFlag::query()->get()->each(
                function (FeatureFlag $flag) use ($tenantId, $planId, &$result): void {
                    if ($flag->hasOpinionFor($tenantId, $planId)) {
                        $result[$flag->key] = $flag->isEnabledFor($tenantId, $planId);
                    }
                },
            );

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
