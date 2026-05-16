<?php

declare(strict_types=1);

namespace App\Domain\Shared\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'name', 'description', 'is_enabled_globally', 'enabled_for_plans', 'enabled_for_tenants', 'disabled_for_tenants', 'enabled_for_firms', 'disabled_for_firms', 'rollout_percentage'])]
class FeatureFlag extends Model
{
    protected function casts(): array
    {
        return [
            'is_enabled_globally'  => 'boolean',
            'enabled_for_plans'    => 'array',
            'enabled_for_tenants'  => 'array',
            'disabled_for_tenants' => 'array',
            'enabled_for_firms'    => 'array',
            'disabled_for_firms'   => 'array',
        ];
    }

    /**
     * SuperAdmin escape hatch for firm-level overrides. Returns:
     *   true  — feature is force-ON for this firm
     *   false — feature is force-OFF for this firm
     *   null  — no firm-level opinion; caller should fall back to firm tier
     */
    public function firmOverride(int $firmId): ?bool
    {
        if (in_array($firmId, $this->disabled_for_firms ?? [], true)) return false;
        if (in_array($firmId, $this->enabled_for_firms ?? [], true))  return true;
        return null;
    }

    /**
     * Check if this feature is enabled for a specific tenant.
     */
    public function isEnabledFor(int $tenantId, ?int $planId = null): bool
    {
        // Explicit disable takes highest priority
        if (in_array($tenantId, $this->disabled_for_tenants ?? [])) {
            return false;
        }

        // Explicit enable per tenant
        if (in_array($tenantId, $this->enabled_for_tenants ?? [])) {
            return true;
        }

        // Plan-level enable
        if ($planId && in_array($planId, $this->enabled_for_plans ?? [])) {
            return true;
        }

        // Global enable
        if ($this->is_enabled_globally) {
            return true;
        }

        // Gradual rollout (deterministic based on tenant ID)
        if ($this->rollout_percentage) {
            $percentage = (int) $this->rollout_percentage;
            if ($percentage > 0) {
                $hash = crc32("{$this->key}:{$tenantId}");

                return (abs($hash) % 100) < $percentage;
            }
        }

        return false;
    }

    /**
     * Whether this flag carries an *explicit* opinion for the tenant/plan.
     *
     * The resolver in `AuthController::tenantFeatures()` layers admin
     * overrides on top of the plan bundle. When a flag row exists with
     * no opinion (e.g. seeded with empty arrays), `isEnabledFor()` returns
     * `false` — which would silently override the plan bundle to `false`.
     * Layer-2 must call this method first and only overlay when it returns
     * `true`, so the plan bundle stays authoritative by default.
     */
    public function hasOpinionFor(int $tenantId, ?int $planId = null): bool
    {
        if (in_array($tenantId, $this->disabled_for_tenants ?? [], true)) {
            return true;
        }
        if (in_array($tenantId, $this->enabled_for_tenants ?? [], true)) {
            return true;
        }
        if ($planId !== null && in_array($planId, $this->enabled_for_plans ?? [], true)) {
            return true;
        }
        if ($this->is_enabled_globally) {
            return true;
        }
        if (! empty($this->rollout_percentage)) {
            return true;
        }

        return false;
    }
}
