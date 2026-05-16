<?php

declare(strict_types=1);

use App\Domain\Subscription\Enums\DiscountType;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Coupon;
use App\Domain\Subscription\Models\Plan;
use App\Domain\Subscription\Models\Subscription;
use App\Filament\Admin\Resources\SubscriptionResource\Pages\ListSubscriptions;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->superAdmin = createSuperAdmin();
});

describe('Coupon model', function (): void {

    it('computes a percent discount capped at the price', function (): void {
        $c = new Coupon([
            'code' => 'PCT25',
            'discount_type' => DiscountType::Percent,
            'discount_value' => 25,
            'currency' => 'EGP',
        ]);

        expect($c->discountFor(200.0))->toBe(50.0)
            ->and($c->discountFor(10.0))->toBe(2.5);
    });

    it('computes a fixed discount capped at the price', function (): void {
        $c = new Coupon([
            'code' => 'FIX100',
            'discount_type' => DiscountType::Fixed,
            'discount_value' => 100,
            'currency' => 'EGP',
        ]);

        expect($c->discountFor(299.0))->toBe(100.0)
            ->and($c->discountFor(50.0))->toBe(50.0);
    });

    it('scopeRedeemable filters out inactive / expired / exhausted', function (): void {
        Coupon::create([
            'code' => 'OK', 'discount_type' => DiscountType::Percent, 'discount_value' => 10,
            'currency' => 'EGP', 'is_active' => true, 'used_count' => 0,
        ]);
        Coupon::create([
            'code' => 'INACTIVE', 'discount_type' => DiscountType::Percent, 'discount_value' => 10,
            'currency' => 'EGP', 'is_active' => false,
        ]);
        Coupon::create([
            'code' => 'EXPIRED', 'discount_type' => DiscountType::Percent, 'discount_value' => 10,
            'currency' => 'EGP', 'is_active' => true, 'expires_at' => now()->subDay(),
        ]);
        Coupon::create([
            'code' => 'USED_UP', 'discount_type' => DiscountType::Percent, 'discount_value' => 10,
            'currency' => 'EGP', 'is_active' => true, 'max_uses' => 5, 'used_count' => 5,
        ]);

        $codes = Coupon::query()->redeemable()->pluck('code')->all();

        expect($codes)->toBe(['OK']);
    });

    it('appliesToPlan returns true when plan list is null (all plans allowed)', function (): void {
        $c = new Coupon(['applies_to_plan_ids' => null]);
        expect($c->appliesToPlan(99))->toBeTrue();
    });

    it('appliesToPlan returns true only for listed plans', function (): void {
        $c = new Coupon(['applies_to_plan_ids' => [1, 2, 5]]);
        expect($c->appliesToPlan(2))->toBeTrue()
            ->and($c->appliesToPlan(99))->toBeFalse();
    });
});

describe('Subscription row actions', function (): void {

    it('extend_trial bumps trial_ends_at by N days and records metadata', function (): void {
        $tenant = createTenant();
        $plan = Plan::factory()->starter()->create();
        $start = now()->addDays(3);

        $sub = Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Trial,
            'trial_ends_at' => $start,
            'metadata' => null,
        ]);

        $this->actingAs($this->superAdmin);
        Filament::setCurrentPanel('admin');

        Livewire::test(ListSubscriptions::class)
            ->callTableAction('extend_trial', $sub, ['days' => 14, 'reason' => 'VIP account courtesy'])
            ->assertHasNoTableActionErrors();

        $sub->refresh();

        expect($sub->trial_ends_at->format('Y-m-d'))->toBe($start->copy()->addDays(14)->format('Y-m-d'))
            ->and($sub->metadata['trial_extensions'][0]['days'] ?? null)->toBe(14)
            ->and($sub->metadata['trial_extensions'][0]['reason'] ?? null)->toBe('VIP account courtesy');
    });

    it('apply_coupon reduces price by the percent discount and increments used_count', function (): void {
        $tenant = createTenant();
        $plan = Plan::factory()->starter()->create();
        $sub = Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'price' => 400,
        ]);
        $coupon = Coupon::create([
            'code' => 'LAUNCH25',
            'discount_type' => DiscountType::Percent,
            'discount_value' => 25,
            'currency' => 'EGP',
            'is_active' => true,
        ]);

        $this->actingAs($this->superAdmin);
        Filament::setCurrentPanel('admin');

        Livewire::test(ListSubscriptions::class)
            ->callTableAction('apply_coupon', $sub, ['coupon_id' => $coupon->id])
            ->assertHasNoTableActionErrors();

        $sub->refresh();
        $coupon->refresh();

        expect((float) $sub->price)->toBe(300.00)
            ->and($coupon->used_count)->toBe(1)
            ->and($sub->metadata['coupon_applications'][0]['coupon_code'] ?? null)->toBe('LAUNCH25');
    });

    it('apply_coupon rejects coupons that do not apply to this plan', function (): void {
        $tenant = createTenant();
        $plan = Plan::factory()->starter()->create();
        $otherPlan = Plan::factory()->professional()->create();

        $sub = Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'price' => 400,
        ]);
        $coupon = Coupon::create([
            'code' => 'PRO_ONLY',
            'discount_type' => DiscountType::Percent,
            'discount_value' => 50,
            'currency' => 'EGP',
            'is_active' => true,
            'applies_to_plan_ids' => [$otherPlan->id],
        ]);

        $this->actingAs($this->superAdmin);
        Filament::setCurrentPanel('admin');

        Livewire::test(ListSubscriptions::class)
            ->callTableAction('apply_coupon', $sub, ['coupon_id' => $coupon->id])
            ->assertHasNoTableActionErrors();

        $sub->refresh();
        $coupon->refresh();

        // Price should be unchanged, coupon not consumed.
        expect((float) $sub->price)->toBe(400.00)
            ->and($coupon->used_count)->toBe(0);
    });
});

describe('CouponResource page', function (): void {

    it('loads the index for SuperAdmin', function (): void {
        $this->actingAs($this->superAdmin);
        $this->get('/admin/coupons')->assertOk();
    });

    it('denies non-SuperAdmin access', function (): void {
        $tenant = createTenant();
        $this->actingAs(createAdminUser($tenant));

        $this->get('/admin/coupons')->assertForbidden();
    });
});
