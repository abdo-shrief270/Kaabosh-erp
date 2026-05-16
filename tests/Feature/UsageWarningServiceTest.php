<?php

declare(strict_types=1);

use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Services\UsageWarningService;
use App\Mail\UsageThresholdMail;
use Illuminate\Support\Facades\Mail;

beforeEach(function (): void {
    Mail::fake();

    $this->tenant = createTenant();
    $this->subscription = Subscription::query()
        ->withoutGlobalScopes()
        ->where('tenant_id', $this->tenant->id)
        ->first();

    // Clamp the test plan down to a finite cap on max_users and create just
    // enough users to trip the 80% threshold (5 users out of 5 = 100%).
    $this->subscription->plan->update([
        'limits' => array_merge($this->subscription->plan->limits, [
            'max_users' => 5,
            'max_clients' => 100,
        ]),
    ]);

    $this->service = app(UsageWarningService::class);
});

it('dispatches a warning when a metric crosses 80%', function (): void {
    // 4 active users out of 5 = 80% — the active admin from createAdminUser
    // counts, so we add 3 more active users to land exactly on the threshold.
    \App\Models\User::factory()
        ->count(3)
        ->create(['tenant_id' => $this->tenant->id]);

    \App\Models\User::factory()->admin()->create(['tenant_id' => $this->tenant->id]);

    $sent = $this->service->sweep();

    expect($sent)->toBeGreaterThanOrEqual(1);
    Mail::assertQueued(UsageThresholdMail::class);
});

it('is idempotent within a single day', function (): void {
    \App\Models\User::factory()
        ->count(4)
        ->create(['tenant_id' => $this->tenant->id]);
    \App\Models\User::factory()->admin()->create(['tenant_id' => $this->tenant->id]);

    $first = $this->service->sweep();
    $second = $this->service->sweep();

    // Second sweep on the same day should send nothing — the sentinel in
    // subscription.metadata stops re-mails for already-warned thresholds.
    expect($first)->toBeGreaterThanOrEqual(1)
        ->and($second)->toBe(0);
});

it('skips unlimited and zero limits', function (): void {
    // -1 = unlimited. A boost or feature unlock can leave a metric uncapped;
    // sweeping should ignore those rather than emailing "you've used 100%".
    $this->subscription->plan->update([
        'limits' => array_merge($this->subscription->plan->limits, [
            'max_users' => -1,
            'max_clients' => 0, // 0 = not allowed; also not a warning case
        ]),
    ]);

    \App\Models\User::factory()
        ->count(5)
        ->create(['tenant_id' => $this->tenant->id]);

    $sent = $this->service->sweep();

    expect($sent)->toBe(0);
    Mail::assertNothingQueued();
});
