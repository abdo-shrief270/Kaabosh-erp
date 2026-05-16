<?php

declare(strict_types=1);

use App\Domain\Shared\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;

describe('Tenant Model', function (): void {

    it('can be created with factory', function (): void {
        $tenant = Tenant::factory()->create();

        expect($tenant)->toBeInstanceOf(Tenant::class)
            ->and($tenant->id)->toBeInt()
            ->and($tenant->name)->toBeString()
            ->and($tenant->slug)->toBeString();
    });

    it('has active status by default from factory', function (): void {
        $tenant = Tenant::factory()->create();

        expect($tenant->status)->toBe(TenantStatus::Active);
    });

    it('can be created with trial status', function (): void {
        $tenant = Tenant::factory()->trial()->create();

        expect($tenant->status)->toBe(TenantStatus::Trial)
            ->and($tenant->trial_ends_at)->not->toBeNull()
            ->and($tenant->trial_ends_at->isFuture())->toBeTrue();
    });

    it('detects expired trial correctly', function (): void {
        $tenant = Tenant::factory()->expiredTrial()->create();

        expect($tenant->hasExpiredTrial())->toBeTrue()
            ->and($tenant->isOnTrial())->toBeFalse();
    });

    it('is accessible when active', function (): void {
        $tenant = Tenant::factory()->create(['status' => TenantStatus::Active]);

        expect($tenant->isAccessible())->toBeTrue();
    });

    it('is accessible when on trial', function (): void {
        $tenant = Tenant::factory()->trial()->create();

        expect($tenant->isAccessible())->toBeTrue();
    });

    it('is not accessible when suspended', function (): void {
        $tenant = Tenant::factory()->suspended()->create();

        expect($tenant->isAccessible())->toBeFalse();
    });

    it('is not accessible when cancelled', function (): void {
        $tenant = Tenant::factory()->cancelled()->create();

        expect($tenant->isAccessible())->toBeFalse();
    });

    it('casts settings to array', function (): void {
        $tenant = Tenant::factory()->create([
            'settings' => ['locale' => 'ar', 'currency' => 'EGP'],
        ]);

        expect($tenant->settings)->toBeArray()
            ->and($tenant->settings['locale'])->toBe('ar')
            ->and($tenant->settings['currency'])->toBe('EGP');
    });

    it('uses slug as route key', function (): void {
        $tenant = Tenant::factory()->create();

        expect($tenant->getRouteKeyName())->toBe('slug');
    });

    it('scopes active tenants', function (): void {
        Tenant::factory()->create(['status' => TenantStatus::Active]);
        Tenant::factory()->suspended()->create();
        Tenant::factory()->cancelled()->create();

        $active = Tenant::query()->active()->get();

        expect($active)->toHaveCount(1)
            ->and($active->first()->status)->toBe(TenantStatus::Active);
    });

    it('scopes accessible tenants', function (): void {
        Tenant::factory()->create(['status' => TenantStatus::Active]);
        Tenant::factory()->trial()->create();
        Tenant::factory()->suspended()->create();

        $accessible = Tenant::query()->accessible()->get();

        expect($accessible)->toHaveCount(2);
    });

    it('soft deletes', function (): void {
        $tenant = Tenant::factory()->create();
        $tenant->delete();

        expect(Tenant::query()->count())->toBe(0)
            ->and(Tenant::withTrashed()->count())->toBe(1);
    });
});
