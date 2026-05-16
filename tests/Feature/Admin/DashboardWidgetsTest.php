<?php

declare(strict_types=1);

use App\Domain\Shared\Enums\TenantStatus;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Plan;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionPayment;
use App\Filament\Admin\Widgets\MrrTrendChart;
use App\Filament\Admin\Widgets\RevenueHealthOverview;
use App\Filament\Admin\Widgets\TenantStatusDonut;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget\Stat;

/*
 * Dashboard analytics widget tests. Computations here are heuristic/approximate —
 * they answer "is this in the right ballpark on seeded data?" not "is it penny-accurate".
 */

beforeEach(function (): void {
    $this->superAdmin = createSuperAdmin();
    $this->actingAs($this->superAdmin);
    Filament::setCurrentPanel('admin');
});

/** Invoke a protected method on a widget instance. */
function invokeWidgetStats(object $widget): array
{
    $ref = new ReflectionClass($widget);
    $method = $ref->getMethod('getStats');
    $method->setAccessible(true);

    return $method->invoke($widget);
}

function statValue(Stat $stat): string
{
    $ref = new ReflectionClass($stat);
    $prop = $ref->getProperty('value');
    $prop->setAccessible(true);

    return (string) $prop->getValue($stat);
}

function statLabel(Stat $stat): string
{
    $ref = new ReflectionClass($stat);
    $prop = $ref->getProperty('label');
    $prop->setAccessible(true);

    return (string) $prop->getValue($stat);
}

describe('RevenueHealthOverview', function (): void {

    it('computes a non-zero churn percentage from seeded subscriptions', function (): void {
        $plan = Plan::factory()->starter()->create();

        $tenantA = createTenant(['status' => TenantStatus::Cancelled]);
        $tenantB = createTenant(['status' => TenantStatus::Active]);

        // One sub created 60 days ago, cancelled 7 days ago — counts as churn.
        Subscription::factory()->create([
            'tenant_id' => $tenantA->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Cancelled,
            'created_at' => now()->subDays(60),
            'cancelled_at' => now()->subDays(7),
        ]);

        // One active sub created 60 days ago — denominator only.
        Subscription::factory()->create([
            'tenant_id' => $tenantB->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'created_at' => now()->subDays(60),
        ]);

        $stats = invokeWidgetStats(new RevenueHealthOverview);
        $churn = $stats[0];

        // 1 cancelled of 2 active at window start = 50%.
        expect(statLabel($churn))->toBe('Churn rate (30d)')
            ->and(statValue($churn))->toBe('50.0%');
    });

    it('counts a recently-activated former trial in the conversion numerator', function (): void {
        $plan = Plan::factory()->starter()->create();
        $tenant = createTenant();

        // Trial ended 5 days ago; sub moved to Active and updated 2 days ago.
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'trial_ends_at' => now()->subDays(5),
            'updated_at' => now()->subDays(2),
            'created_at' => now()->subDays(20),
        ]);

        $stats = invokeWidgetStats(new RevenueHealthOverview);
        $conversion = $stats[1];

        // 1 converted of 1 trial ended = 100%.
        expect(statLabel($conversion))->toBe('Trial → Active (30d)')
            ->and(statValue($conversion))->toBe('100.0%');
    });

    it('computes payment failure rate around 33 percent for 1-of-3 failures', function (): void {
        $plan = Plan::factory()->starter()->create();
        $tenant = createTenant();
        $sub = Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
        ]);

        SubscriptionPayment::factory()->completed()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $sub->id,
        ]);
        SubscriptionPayment::factory()->completed()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $sub->id,
        ]);
        SubscriptionPayment::factory()->failed()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $sub->id,
        ]);

        $stats = invokeWidgetStats(new RevenueHealthOverview);
        $failure = $stats[3];

        expect(statLabel($failure))->toBe('Payment failures (30d)')
            ->and(statValue($failure))->toBe('33.3%');
    });
});

describe('MrrTrendChart', function (): void {

    it('returns 12 labels and 12 non-negative values', function (): void {
        $plan = Plan::factory()->starter()->create(); // price_monthly = 299
        $tenant = createTenant();

        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'created_at' => now()->subMonths(6),
        ]);

        $data = (new MrrTrendChart)->getData();

        expect($data['labels'])->toHaveCount(12)
            ->and($data['datasets'][0]['data'])->toHaveCount(12);

        foreach ($data['datasets'][0]['data'] as $value) {
            expect($value)->toBeGreaterThanOrEqual(0.0);
        }

        // The most recent month must include this active subscription.
        expect(end($data['datasets'][0]['data']))->toBeGreaterThan(0.0);
    });
});

describe('TenantStatusDonut', function (): void {

    it('produces one slice per status present in the database', function (): void {
        createTenant(['status' => TenantStatus::Active]);
        createTenant(['status' => TenantStatus::Active]);
        createTenant(['status' => TenantStatus::Trial]);
        createTenant(['status' => TenantStatus::Suspended]);

        $data = (new TenantStatusDonut)->getData();

        expect($data['labels'])->toHaveCount(3)
            ->and($data['datasets'][0]['data'])->toHaveCount(3)
            ->and(array_sum($data['datasets'][0]['data']))->toBe(4);
    });
});

describe('admin dashboard page', function (): void {

    it('loads for a SuperAdmin', function (): void {
        $this->get('/admin')->assertOk();
    });
});
