<?php

declare(strict_types=1);

use App\Domain\Shared\Models\FeatureFlag;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Plan;
use App\Domain\Subscription\Models\Subscription;
use Illuminate\Support\Facades\Cache;

/**
 * Integration tests for the `feature:` middleware wired onto API route groups.
 * Verifies plan-based gating returns 403 feature_not_available when the tenant's
 * plan doesn't include the feature, and that admin FeatureFlag overrides bypass
 * the check.
 */
beforeEach(function (): void {
    Cache::flush();
    // Seed plans + roles — required by permission middleware that runs after feature middleware.
    $this->artisan('db:seed', ['--class' => 'PermissionSeeder'])->assertSuccessful();
    $this->artisan('db:seed', ['--class' => 'PlanSeeder'])->assertSuccessful();
});

function subscribeTenantToPlan($tenant, string $planSlug): Subscription
{
    $plan = Plan::where('slug', $planSlug)->firstOrFail();

    return Subscription::factory()
        ->active()
        ->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
        ]);
}

it('allows access to a feature included in the plan', function (): void {
    $tenant = createTenant();
    subscribeTenantToPlan($tenant, 'professional');
    $admin = createAdminUser($tenant);
    actingAsUser($admin);

    $response = $this->withHeader('X-Tenant', $tenant->slug)
        ->getJson('/api/v1/clients');

    // Professional includes 'clients' — must NOT be blocked by feature middleware.
    expect($response->json('error'))->not->toBe('feature_not_available');
    expect($response->json('error'))->not->toBe('subscription_inactive');
});

it('blocks access when plan does not include the feature', function (): void {
    $tenant = createTenant();
    subscribeTenantToPlan($tenant, 'starter'); // starter lacks e_invoice
    $admin = createAdminUser($tenant);
    actingAsUser($admin);

    $response = $this->withHeader('X-Tenant', $tenant->slug)
        ->getJson('/api/v1/eta/documents');

    $response->assertStatus(403)
        ->assertJsonPath('error', 'feature_not_available');
});

it('blocks access when tenant has no subscription', function (): void {
    $tenant = createTenant();
    // createTenant() auto-provisions a trial subscription; strip it so we
    // exercise the no-subscription path.
    Subscription::where('tenant_id', $tenant->id)->delete();

    $admin = createAdminUser($tenant);
    actingAsUser($admin);

    $response = $this->withHeader('X-Tenant', $tenant->slug)
        ->getJson('/api/v1/eta/documents');

    $response->assertStatus(403)
        ->assertJsonPath('error', 'subscription_inactive');
});

it('allows access when a global FeatureFlag overrides the plan', function (): void {
    $tenant = createTenant();
    subscribeTenantToPlan($tenant, 'starter'); // starter lacks e_invoice
    $admin = createAdminUser($tenant);
    actingAsUser($admin);

    FeatureFlag::create([
        'key' => 'e_invoice',
        'name' => 'ETA E-Invoice',
        'is_enabled_globally' => true,
        'rollout_percentage' => 100,
    ]);

    Cache::flush(); // invalidate FeatureFlagService cache

    $response = $this->withHeader('X-Tenant', $tenant->slug)
        ->getJson('/api/v1/eta/documents');

    // With flag enabled, must NOT be blocked by feature middleware.
    expect($response->json('error'))->not->toBe('feature_not_available');
});
