<?php

declare(strict_types=1);

use App\Domain\Shared\Enums\UserRole;

describe('UserRole Enum', function (): void {

    it('has correct values', function (): void {
        expect(UserRole::SuperAdmin->value)->toBe('super_admin')
            ->and(UserRole::Admin->value)->toBe('admin')
            ->and(UserRole::Accountant->value)->toBe('accountant')
            ->and(UserRole::Auditor->value)->toBe('auditor')
            ->and(UserRole::Client->value)->toBe('client');
    });

    it('identifies tenant-level roles', function (): void {
        expect(UserRole::Admin->isTenantLevel())->toBeTrue()
            ->and(UserRole::Accountant->isTenantLevel())->toBeTrue()
            ->and(UserRole::Auditor->isTenantLevel())->toBeTrue()
            ->and(UserRole::Client->isTenantLevel())->toBeTrue()
            ->and(UserRole::SuperAdmin->isTenantLevel())->toBeFalse();
    });

    it('identifies platform-level roles', function (): void {
        expect(UserRole::SuperAdmin->isPlatformLevel())->toBeTrue()
            ->and(UserRole::Admin->isPlatformLevel())->toBeFalse();
    });
});
