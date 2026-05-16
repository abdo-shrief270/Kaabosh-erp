<?php

declare(strict_types=1);

use App\Domain\Subscription\Models\Plan;

it('every feature referenced in plan_bundles exists in the catalog', function (): void {
    $catalogKeys = array_keys(config('features.catalog', []));
    $bundles = config('features.plan_bundles', []);

    $unknown = [];
    foreach ($bundles as $planSlug => $bundledFeatures) {
        foreach ($bundledFeatures as $feature) {
            if (! in_array($feature, $catalogKeys, true)) {
                $unknown[] = "{$planSlug}:{$feature}";
            }
        }
    }

    expect($unknown)->toBe([]);
});

it('seeded plans expose every catalog feature as a boolean', function (): void {
    $this->artisan('db:seed', ['--class' => 'PlanSeeder'])->assertSuccessful();

    $catalogKeys = array_keys(config('features.catalog', []));

    foreach (['free_trial', 'starter', 'professional', 'enterprise'] as $slug) {
        $plan = Plan::where('slug', $slug)->first();
        expect($plan)->not->toBeNull();

        foreach ($catalogKeys as $key) {
            expect($plan->features)->toHaveKey($key);
            expect($plan->features[$key])->toBeBool();
        }
    }
});

it('enterprise plan enables every catalog feature', function (): void {
    $this->artisan('db:seed', ['--class' => 'PlanSeeder'])->assertSuccessful();

    $plan = Plan::where('slug', 'enterprise')->first();
    $catalogKeys = array_keys(config('features.catalog', []));

    foreach ($catalogKeys as $key) {
        expect($plan->hasFeature($key))->toBeTrue();
    }
});

it('free_trial enables only clients and documents', function (): void {
    $this->artisan('db:seed', ['--class' => 'PlanSeeder'])->assertSuccessful();

    $plan = Plan::where('slug', 'free_trial')->first();

    expect($plan->hasFeature('clients'))->toBeTrue();
    expect($plan->hasFeature('documents'))->toBeTrue();
    expect($plan->hasFeature('invoicing'))->toBeFalse();
    expect($plan->hasFeature('e_invoice'))->toBeFalse();
    expect($plan->hasFeature('banking'))->toBeFalse();
});
