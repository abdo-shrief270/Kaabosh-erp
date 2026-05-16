<?php

declare(strict_types=1);

use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Plan;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Services\UsageService;
use App\Http\Middleware\CheckLimit;
use App\Http\Middleware\CheckSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

beforeEach(function (): void {
    $this->tenant = createTenant();
    $this->admin = createAdminUser($this->tenant);
    actingAsUser($this->admin);

    $this->plan = Plan::factory()->starter()->create();
});

describe('CheckSubscription middleware', function (): void {

    it('allows access with an active subscription', function (): void {
        Subscription::factory()->active()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
        ]);

        app()->instance('tenant.id', $this->tenant->id);

        $middleware = new CheckSubscription;
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, fn ($req) => new Response('OK', 200));

        expect($response->getStatusCode())->toBe(200);
        expect($request->attributes->get('subscription'))->not->toBeNull();
    });

    it('blocks access with an expired subscription', function (): void {
        Subscription::factory()->expired()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
        ]);

        app()->instance('tenant.id', $this->tenant->id);

        $middleware = new CheckSubscription;
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, fn ($req) => new Response('OK', 200));

        expect($response->getStatusCode())->toBe(403);

        $data = json_decode($response->getContent(), true);
        expect($data['error'])->toBe('subscription_inactive');
    });

    it('blocks access with no subscription', function (): void {
        // createTenant() auto-provisions a trial subscription so tenant-scoped
        // HTTP tests pass the CheckSubscription middleware out of the box.
        // This test wants the no-subscription state, so wipe it first.
        Subscription::where('tenant_id', $this->tenant->id)->delete();

        app()->instance('tenant.id', $this->tenant->id);

        $middleware = new CheckSubscription;
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, fn ($req) => new Response('OK', 200));

        expect($response->getStatusCode())->toBe(403);

        $data = json_decode($response->getContent(), true);
        expect($data['error'])->toBe('subscription_inactive');
    });

    it('allows access with a trial subscription', function (): void {
        Subscription::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'status' => SubscriptionStatus::Trial,
            'trial_ends_at' => now()->addDays(7),
        ]);

        app()->instance('tenant.id', $this->tenant->id);

        $middleware = new CheckSubscription;
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, fn ($req) => new Response('OK', 200));

        expect($response->getStatusCode())->toBe(200);
    });
});

describe('CheckLimit middleware', function (): void {

    it('allows when under the limit', function (): void {
        $mockUsageService = Mockery::mock(UsageService::class);
        $mockUsageService->shouldReceive('checkLimit')
            ->with('clients')
            ->once()
            ->andReturn(true);

        $middleware = new CheckLimit($mockUsageService);
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, fn ($req) => new Response('OK', 200), 'clients');

        expect($response->getStatusCode())->toBe(200);
    });

    it('blocks when at or over the limit', function (): void {
        $mockUsageService = Mockery::mock(UsageService::class);
        $mockUsageService->shouldReceive('checkLimit')
            ->with('clients')
            ->once()
            ->andReturn(false);

        $middleware = new CheckLimit($mockUsageService);
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, fn ($req) => new Response('OK', 200), 'clients');

        expect($response->getStatusCode())->toBe(403);

        $data = json_decode($response->getContent(), true);
        expect($data['error'])->toBe('limit_exceeded');
        expect($data['resource'])->toBe('clients');
    });
});
