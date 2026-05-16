<?php

declare(strict_types=1);

use App\Domain\Subscription\Enums\AddOnBillingCycle;
use App\Domain\Subscription\Enums\AddOnType;
use App\Domain\Subscription\Enums\SubscriptionAddOnStatus;
use App\Domain\Subscription\Models\AddOn;
use App\Domain\Subscription\Models\AddOnCredit;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionAddOn;
use App\Domain\Subscription\Services\AddOnService;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->tenant = createTenant();
    $this->subscription = Subscription::query()
        ->withoutGlobalScopes()
        ->where('tenant_id', $this->tenant->id)
        ->first();
    $this->service = app(AddOnService::class);
});

describe('AddOnService::getEffectiveLimits', function () {
    it('returns plan limits when no add-ons are active', function (): void {
        $limits = $this->service->getEffectiveLimits($this->tenant->id);

        expect($limits['max_users'] ?? null)->toBe(-1)
            ->and($limits['max_clients'] ?? null)->toBe(-1);
    });

    it('adds boost contributions on top of plan limits', function (): void {
        // Drop the test plan from "unlimited" to a finite cap so the boost is
        // observable.
        $this->subscription->plan->update([
            'limits' => array_merge($this->subscription->plan->limits, [
                'max_users' => 5,
                'max_clients' => 25,
            ]),
        ]);

        $boost = AddOn::factory()->create([
            'type' => AddOnType::Boost->value,
            'billing_cycle' => AddOnBillingCycle::Monthly->value,
            'boost' => ['max_users' => 5, 'max_clients' => 10],
        ]);

        SubscriptionAddOn::factory()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $this->subscription->id,
            'add_on_id' => $boost->id,
            'quantity' => 1,
        ]);

        $limits = $this->service->getEffectiveLimits($this->tenant->id);

        expect($limits['max_users'])->toBe(10)
            ->and($limits['max_clients'])->toBe(35);
    });

    it('multiplies boost by quantity for stacked add-ons', function (): void {
        $this->subscription->plan->update([
            'limits' => array_merge($this->subscription->plan->limits, ['max_users' => 5]),
        ]);

        $boost = AddOn::factory()->create([
            'boost' => ['max_users' => 5],
        ]);

        SubscriptionAddOn::factory()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $this->subscription->id,
            'add_on_id' => $boost->id,
            'quantity' => 3,
        ]);

        expect($this->service->getEffectiveLimits($this->tenant->id)['max_users'])->toBe(20);
    });

    it('keeps unlimited as unlimited even with boosts', function (): void {
        $boost = AddOn::factory()->create(['boost' => ['max_users' => 5]]);
        SubscriptionAddOn::factory()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $this->subscription->id,
            'add_on_id' => $boost->id,
        ]);

        // Test plan is already unlimited (-1) for max_users.
        expect($this->service->getEffectiveLimits($this->tenant->id)['max_users'])->toBe(-1);
    });

    it('ignores cancelled and expired add-ons', function (): void {
        $this->subscription->plan->update([
            'limits' => array_merge($this->subscription->plan->limits, ['max_users' => 5]),
        ]);

        $boost = AddOn::factory()->create(['boost' => ['max_users' => 5]]);

        SubscriptionAddOn::factory()->cancelled()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $this->subscription->id,
            'add_on_id' => $boost->id,
        ]);

        expect($this->service->getEffectiveLimits($this->tenant->id)['max_users'])->toBe(5);
    });
});

describe('AddOnService::purchase', function () {
    it('creates a subscription_add_on row aligned to the subscription period', function (): void {
        $boost = AddOn::factory()->create([
            'boost' => ['max_users' => 5],
            'price_monthly' => 99,
        ]);

        $this->subscription->update([
            'current_period_start' => now()->startOfMonth()->toDateString(),
            'current_period_end' => now()->endOfMonth()->toDateString(),
        ]);

        $row = $this->service->purchase($boost, ['quantity' => 2]);

        expect($row)->toBeInstanceOf(SubscriptionAddOn::class)
            ->and($row->subscription_id)->toBe($this->subscription->id)
            ->and($row->quantity)->toBe(2)
            ->and($row->status)->toBe(SubscriptionAddOnStatus::Active)
            ->and((string) $row->price)->toBe('99.00')
            ->and($row->current_period_start?->toDateString())->toBe(now()->startOfMonth()->toDateString())
            ->and($row->current_period_end?->toDateString())->toBe(now()->endOfMonth()->toDateString());
    });

    it('seeds a credit wallet row for credit_pack purchases', function (): void {
        $pack = AddOn::factory()->creditPack('sms', 1000)->create([
            'price_once' => 149,
        ]);

        $row = $this->service->purchase($pack, ['quantity' => 2]);

        $credits = AddOnCredit::query()
            ->withoutGlobalScopes()
            ->where('subscription_add_on_id', $row->id)
            ->first();

        expect($credits)->not->toBeNull()
            ->and($credits->kind)->toBe('sms')
            ->and($credits->quantity_total)->toBe(2000) // 1000 × 2
            ->and($credits->quantity_used)->toBe(0);
    });

    it('rejects inactive add-ons', function (): void {
        $inactive = AddOn::factory()->inactive()->create();

        expect(fn () => $this->service->purchase($inactive))
            ->toThrow(ValidationException::class);
    });
});

describe('AddOnService::cancelAtPeriodEnd', function () {
    it('flags cancel_at_period_end without expiring the row', function (): void {
        $boost = AddOn::factory()->create();
        $row = SubscriptionAddOn::factory()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $this->subscription->id,
            'add_on_id' => $boost->id,
        ]);

        $result = $this->service->cancelAtPeriodEnd($row);

        expect($result->cancel_at_period_end)->toBeTrue()
            ->and($result->status)->toBe(SubscriptionAddOnStatus::Active)
            ->and($result->cancelled_at)->not->toBeNull()
            ->and($result->expires_at)->toBeNull();
    });
});

describe('AddOnService::consumeCredits', function () {
    it('drains FIFO across multiple credit packs', function (): void {
        $pack = AddOn::factory()->creditPack('sms', 100)->create();

        $first = $this->service->purchase($pack);
        // Force a different created_at so FIFO order is deterministic — Carbon
        // sub-second precision would otherwise tie the sort.
        AddOnCredit::query()
            ->withoutGlobalScopes()
            ->where('subscription_add_on_id', $first->id)
            ->update(['created_at' => now()->subDay()]);

        $this->service->purchase($pack);

        $consumed = $this->service->consumeCredits('sms', 150);

        expect($consumed)->toBe(150)
            ->and($this->service->creditBalance('sms', $this->tenant->id))->toBe(50);

        // First pack should be fully drained, second should have 50 remaining.
        $packs = AddOnCredit::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $this->tenant->id)
            ->orderBy('created_at')
            ->get();

        expect($packs[0]->remaining())->toBe(0)
            ->and($packs[1]->remaining())->toBe(50);
    });

    it('returns the actually-consumed amount when balance is insufficient', function (): void {
        $pack = AddOn::factory()->creditPack('sms', 50)->create();
        $this->service->purchase($pack);

        $consumed = $this->service->consumeCredits('sms', 200);

        expect($consumed)->toBe(50)
            ->and($this->service->creditBalance('sms', $this->tenant->id))->toBe(0);
    });
});

describe('AddOnService::confirmPayment / failPayment', function () {
    it('starts online purchases as pending and only seeds credits on confirmation', function (): void {
        $pack = AddOn::factory()->creditPack('sms', 500)->create();

        $row = $this->service->purchase($pack, [
            'gateway' => 'paymob',
            'gateway_payment_id' => 'ADDON-42-abc',
        ]);

        // Pending rows shouldn't have any credit balance until the gateway
        // confirms — otherwise a failed payment would leak free credits.
        expect($row->status)->toBe(SubscriptionAddOnStatus::Pending)
            ->and(AddOnCredit::query()->withoutGlobalScopes()->where('subscription_add_on_id', $row->id)->count())->toBe(0);

        $confirmed = $this->service->confirmPayment('ADDON-42-abc');

        expect($confirmed)->not->toBeNull()
            ->and($confirmed->status)->toBe(SubscriptionAddOnStatus::Active)
            ->and((int) AddOnCredit::query()->withoutGlobalScopes()->where('subscription_add_on_id', $row->id)->sum('quantity_total'))->toBe(500);
    });

    it('is idempotent on repeated webhook deliveries', function (): void {
        $boost = AddOn::factory()->create(['boost' => ['max_users' => 5]]);

        $this->service->purchase($boost, [
            'gateway' => 'paymob',
            'gateway_payment_id' => 'ADDON-77-xyz',
        ]);

        $first = $this->service->confirmPayment('ADDON-77-xyz');
        $second = $this->service->confirmPayment('ADDON-77-xyz');

        // Second call returns the same row unchanged — no double-seeding,
        // no exception. Paymob retries should be safe.
        expect($first->status)->toBe(SubscriptionAddOnStatus::Active)
            ->and($second->status)->toBe(SubscriptionAddOnStatus::Active)
            ->and($second->id)->toBe($first->id);
    });

    it('marks pending rows as failed on a rejected payment', function (): void {
        $boost = AddOn::factory()->create(['boost' => ['max_users' => 5]]);

        $this->service->purchase($boost, [
            'gateway' => 'paymob',
            'gateway_payment_id' => 'ADDON-99-fail',
        ]);

        $failed = $this->service->failPayment('ADDON-99-fail', 'Card declined');

        expect($failed?->status)->toBe(SubscriptionAddOnStatus::Failed)
            ->and($failed->metadata['failure_reason'] ?? null)->toBe('Card declined');
    });

    it('returns null when the gateway id matches no row', function (): void {
        expect($this->service->confirmPayment('NEVER-EXISTED'))->toBeNull()
            ->and($this->service->failPayment('NEVER-EXISTED'))->toBeNull();
    });
});

describe('AddOnService::expireDue', function () {
    it('cancels rows where cancel_at_period_end and current_period_end has passed', function (): void {
        $boost = AddOn::factory()->create();
        $row = SubscriptionAddOn::factory()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $this->subscription->id,
            'add_on_id' => $boost->id,
            'cancel_at_period_end' => true,
            'current_period_end' => now()->subDay()->toDateString(),
        ]);

        $touched = $this->service->expireDue();

        expect($touched)->toBeGreaterThanOrEqual(1)
            ->and($row->fresh()->status)->toBe(SubscriptionAddOnStatus::Cancelled);
    });

    it('expires rows where expires_at has passed', function (): void {
        $pack = AddOn::factory()->creditPack()->create();
        $row = SubscriptionAddOn::factory()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $this->subscription->id,
            'add_on_id' => $pack->id,
            'expires_at' => now()->subHour(),
        ]);

        $this->service->expireDue();

        expect($row->fresh()->status)->toBe(SubscriptionAddOnStatus::Expired);
    });
});
