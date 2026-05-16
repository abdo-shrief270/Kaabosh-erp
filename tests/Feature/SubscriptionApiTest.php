<?php

declare(strict_types=1);

use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Plan;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionPayment;
use App\Domain\Subscription\Services\SubscriptionService;
use App\Domain\Subscription\Services\UsageService;
use Illuminate\Database\Eloquent\Collection;

beforeEach(function (): void {
    $this->tenant = createTenant();
    $this->admin = createAdminUser($this->tenant);
    actingAsUser($this->admin);

    $this->plan = Plan::factory()->starter()->create();
});

describe('POST /api/v1/subscription/subscribe', function (): void {

    it('starts a trial subscription', function (): void {
        $trialSub = Subscription::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'status' => SubscriptionStatus::Trial,
        ]);

        $this->mock(SubscriptionService::class, function ($mock) use ($trialSub) {
            $mock->shouldReceive('subscribe')
                ->once()
                ->andReturn($trialSub->load('plan'));
        });

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson('/api/v1/subscription/subscribe', [
                'plan_id' => $this->plan->id,
                'billing_cycle' => 'monthly',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'trial')
            ->assertJsonPath('data.plan_id', $this->plan->id);
    });

    it('cannot subscribe without a plan_id', function (): void {
        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson('/api/v1/subscription/subscribe', [
                'billing_cycle' => 'monthly',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['plan_id']);
    });

    it('subscribes to a plan with active status', function (): void {
        $activeSub = Subscription::factory()->active()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
        ]);

        $this->mock(SubscriptionService::class, function ($mock) use ($activeSub) {
            $mock->shouldReceive('subscribe')
                ->once()
                ->andReturn($activeSub->load('plan'));
        });

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson('/api/v1/subscription/subscribe', [
                'plan_id' => $this->plan->id,
                'billing_cycle' => 'monthly',
                'gateway' => 'paymob',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'active');
    });
});

describe('GET /api/v1/subscription', function (): void {

    it('shows current subscription with plan details', function (): void {
        $subscription = Subscription::factory()->active()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
        ]);

        $this->mock(SubscriptionService::class, function ($mock) use ($subscription) {
            $mock->shouldReceive('getCurrentSubscription')
                ->once()
                ->andReturn($subscription);
        });

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson('/api/v1/subscription');

        $response->assertStatus(201)
            ->assertJsonPath('data.id', $subscription->id)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonStructure([
                'data' => [
                    'id', 'plan_id', 'status', 'billing_cycle', 'price',
                    'currency', 'is_accessible', 'trial_days_remaining', 'days_until_renewal',
                    'plan',
                ],
            ]);
    });

    it('returns null data when no subscription exists', function (): void {
        $this->mock(SubscriptionService::class, function ($mock) {
            $mock->shouldReceive('getCurrentSubscription')
                ->once()
                ->andReturn(null);
        });

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson('/api/v1/subscription');

        $response->assertOk()
            ->assertJsonPath('data', null);
    });
});

describe('POST /api/v1/subscription/cancel', function (): void {

    it('cancels the current subscription', function (): void {
        $subscription = Subscription::factory()->cancelled()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
        ]);

        $this->mock(SubscriptionService::class, function ($mock) use ($subscription) {
            $mock->shouldReceive('getCurrentSubscription')
                ->once()
                ->andReturn($subscription);
            $mock->shouldReceive('cancel')
                ->once()
                ->andReturn($subscription);
        });

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson('/api/v1/subscription/cancel');

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'cancelled');
    });
});

describe('POST /api/v1/subscription/renew', function (): void {

    it('renews the current subscription', function (): void {
        $subscription = Subscription::factory()->active()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
        ]);

        $this->mock(SubscriptionService::class, function ($mock) use ($subscription) {
            $mock->shouldReceive('getCurrentSubscription')
                ->once()
                ->andReturn($subscription);
            $mock->shouldReceive('renew')
                ->once()
                ->andReturn($subscription);
        });

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson('/api/v1/subscription/renew');

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'active');
    });
});

describe('POST /api/v1/subscription/change-plan', function (): void {

    it('changes to a different plan (upgrade)', function (): void {
        $proPlan = Plan::factory()->professional()->create();

        $subscription = Subscription::factory()->active()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $proPlan->id,
        ]);

        $this->mock(SubscriptionService::class, function ($mock) use ($subscription) {
            $mock->shouldReceive('getCurrentSubscription')
                ->once()
                ->andReturn($subscription);
            $mock->shouldReceive('changePlan')
                ->once()
                ->andReturn($subscription);
        });

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson('/api/v1/subscription/change-plan', [
                'plan_id' => $proPlan->id,
                'billing_cycle' => 'annual',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.plan_id', $proPlan->id);
    });
});

describe('GET /api/v1/subscription/usage', function (): void {

    it('returns current usage vs limits', function (): void {
        $usageData = [
            'users' => ['current' => 3, 'limit' => 5, 'percentage' => 60],
            'clients' => ['current' => 12, 'limit' => 50, 'percentage' => 24],
            'invoices' => ['current' => 45, 'limit' => 200, 'percentage' => 22],
        ];

        $this->mock(UsageService::class, function ($mock) use ($usageData) {
            $mock->shouldReceive('getUsage')
                ->once()
                ->andReturn($usageData);
        });

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson('/api/v1/subscription/usage');

        $response->assertOk()
            ->assertJsonPath('data.users.current', 3)
            ->assertJsonPath('data.users.limit', 5)
            ->assertJsonPath('data.clients.current', 12);
    });
});

describe('GET /api/v1/subscription/usage-history', function (): void {

    it('returns usage history', function (): void {
        $history = [
            ['date' => '2026-03-30', 'users_count' => 3, 'clients_count' => 12, 'invoices_count' => 45],
            ['date' => '2026-03-29', 'users_count' => 3, 'clients_count' => 11, 'invoices_count' => 42],
        ];

        $this->mock(UsageService::class, function ($mock) use ($history) {
            $mock->shouldReceive('getUsageHistory')
                ->once()
                ->andReturn(new Collection($history));
        });

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson('/api/v1/subscription/usage-history');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });
});

describe('GET /api/v1/subscription/payments', function (): void {

    it('lists subscription payments', function (): void {
        $subscription = Subscription::factory()->active()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
        ]);

        SubscriptionPayment::factory()->completed()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $subscription->id,
        ]);

        $this->mock(SubscriptionService::class, function ($mock) use ($subscription) {
            $mock->shouldReceive('getCurrentSubscription')
                ->once()
                ->andReturn($subscription);
        });

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson('/api/v1/subscription/payments');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [[
                    'id', 'subscription_id', 'amount', 'currency', 'status',
                    'gateway', 'paid_at', 'created_at',
                ]],
            ]);
    });
});

describe('expired trial scenario', function (): void {

    it('expired trial subscription is not accessible', function (): void {
        $subscription = Subscription::factory()->expired()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
        ]);

        expect($subscription->isAccessible())->toBeFalse();
        expect($subscription->status)->toBe(SubscriptionStatus::Expired);
    });
});
