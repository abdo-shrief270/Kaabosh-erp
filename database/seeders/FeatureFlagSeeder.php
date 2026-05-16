<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Shared\Models\FeatureFlag;
use Illuminate\Database\Seeder;

/**
 * Seeds one FeatureFlag row per feature in `config/features.php` catalog.
 *
 * Defaults:
 *   - is_enabled_globally = false  → plan bundles still drive baseline access.
 *   - enabled_for_plans   = []     → no plan-level override.
 *   - enabled_for_tenants = []     → no per-tenant force-on.
 *   - disabled_for_tenants = []    → no per-tenant force-off.
 *   - rollout_percentage  = null
 *
 * With these defaults the seeded rows are inert — the layered resolver in
 * `AuthController::tenantFeatures()` still falls back to the plan bundle when
 * no override is present. The rows exist purely so super-admins can flip a
 * feature on/off for a specific tenant via the TenantResource's "Feature
 * Overrides" tab without having to create the flag from scratch.
 *
 * Idempotent: `updateOrCreate` on `key` keeps admin-edited fields stable
 * across re-seeds, but auto-fills `name`/`description` from the catalog when
 * they're empty so display strings stay in sync with config edits.
 */
class FeatureFlagSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = config('features.catalog', []);

        foreach ($catalog as $key => $meta) {
            $existing = FeatureFlag::query()->where('key', $key)->first();

            if ($existing) {
                // Refresh display strings only — preserve admin overrides.
                $updates = [];
                if (empty($existing->name)) {
                    $updates['name'] = $meta['name_en'] ?? $key;
                }
                if (empty($existing->description) && ! empty($meta['description_en'])) {
                    $updates['description'] = $meta['description_en'];
                }
                if ($updates) {
                    $existing->update($updates);
                }

                continue;
            }

            FeatureFlag::query()->create([
                'key' => $key,
                'name' => $meta['name_en'] ?? $key,
                'description' => $meta['description_en'] ?? null,
                'is_enabled_globally' => false,
                'enabled_for_plans' => [],
                'enabled_for_tenants' => [],
                'disabled_for_tenants' => [],
                'rollout_percentage' => null,
            ]);
        }
    }
}
