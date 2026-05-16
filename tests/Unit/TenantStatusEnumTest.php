<?php

declare(strict_types=1);

use App\Domain\Shared\Enums\TenantStatus;

describe('TenantStatus Enum', function (): void {

    it('has correct values', function (): void {
        expect(TenantStatus::Active->value)->toBe('active')
            ->and(TenantStatus::Suspended->value)->toBe('suspended')
            ->and(TenantStatus::Trial->value)->toBe('trial')
            ->and(TenantStatus::Cancelled->value)->toBe('cancelled');
    });

    it('returns correct labels', function (): void {
        expect(TenantStatus::Active->label())->toBe('Active')
            ->and(TenantStatus::Suspended->label())->toBe('Suspended')
            ->and(TenantStatus::Trial->label())->toBe('Trial')
            ->and(TenantStatus::Cancelled->label())->toBe('Cancelled');
    });

    it('identifies accessible statuses', function (): void {
        expect(TenantStatus::Active->isAccessible())->toBeTrue()
            ->and(TenantStatus::Trial->isAccessible())->toBeTrue()
            ->and(TenantStatus::Suspended->isAccessible())->toBeFalse()
            ->and(TenantStatus::Cancelled->isAccessible())->toBeFalse();
    });
});
