<?php

declare(strict_types=1);

use App\Domain\Subscription\Models\Plan;
use App\Domain\Subscription\Services\PlanFeatureCache;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    Cache::flush();
});

it('returns true when plan has the feature', function (): void {
    $plan = Plan::factory()->create([
        'features' => ['banking' => true, 'payroll' => false],
    ]);

    expect(PlanFeatureCache::has($plan->id, 'banking'))->toBeTrue();
    expect(PlanFeatureCache::has($plan->id, 'payroll'))->toBeFalse();
});

it('returns false for unknown features on the plan', function (): void {
    $plan = Plan::factory()->create([
        'features' => ['clients' => true],
    ]);

    expect(PlanFeatureCache::has($plan->id, 'non_existent_feature'))->toBeFalse();
});

it('returns false when plan does not exist', function (): void {
    expect(PlanFeatureCache::has(999999, 'clients'))->toBeFalse();
});
