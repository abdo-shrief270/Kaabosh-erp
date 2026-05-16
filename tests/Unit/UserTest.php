<?php

declare(strict_types=1);

use App\Domain\Shared\Enums\UserRole;
use App\Models\User;

describe('User Model', function (): void {

    it('can be created with factory', function (): void {
        $user = User::factory()->create();

        expect($user)->toBeInstanceOf(User::class)
            ->and($user->name)->toBeString()
            ->and($user->email)->toBeString();
    });

    it('hashes password automatically', function (): void {
        $user = User::factory()->create(['password' => 'my-secret']);

        expect($user->password)->not->toBe('my-secret')
            ->and(password_verify('my-secret', $user->password))->toBeTrue();
    });

    it('creates super admin without tenant', function (): void {
        $user = User::factory()->superAdmin()->create();

        expect($user->role)->toBe(UserRole::SuperAdmin)
            ->and($user->tenant_id)->toBeNull()
            ->and($user->isSuperAdmin())->toBeTrue()
            ->and($user->isTenantUser())->toBeFalse();
    });

    it('creates admin with tenant', function (): void {
        $user = createAdminUser();

        expect($user->role)->toBe(UserRole::Admin)
            ->and($user->tenant_id)->not->toBeNull()
            ->and($user->isAdmin())->toBeTrue()
            ->and($user->isTenantUser())->toBeTrue();
    });

    it('creates accountant role', function (): void {
        $user = User::factory()->accountant()->create();

        expect($user->role)->toBe(UserRole::Accountant)
            ->and($user->isTenantUser())->toBeTrue();
    });

    it('creates auditor role', function (): void {
        $user = User::factory()->auditor()->create();

        expect($user->role)->toBe(UserRole::Auditor)
            ->and($user->isTenantUser())->toBeTrue();
    });

    it('defaults to client role', function (): void {
        $user = User::factory()->create();

        expect($user->role)->toBe(UserRole::Client);
    });

    it('can be deactivated', function (): void {
        $user = User::factory()->inactive()->create();

        expect($user->is_active)->toBeFalse();
    });

    it('scopes active users', function (): void {
        User::factory()->count(2)->create(['is_active' => true]);
        User::factory()->inactive()->create();

        // Need to bypass tenant scope for counting
        $active = User::query()->withoutGlobalScope('tenant')->active()->count();

        expect($active)->toBe(2);
    });

    it('records login timestamp', function (): void {
        $user = User::factory()->create(['last_login_at' => null]);

        expect($user->last_login_at)->toBeNull();

        $user->recordLogin();
        $user->refresh();

        expect($user->last_login_at)->not->toBeNull();
    });

    it('belongs to a tenant', function (): void {
        $tenant = createTenant();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        expect($user->tenant->id)->toBe($tenant->id);
    });

    it('soft deletes', function (): void {
        $user = User::factory()->create();
        $userId = $user->id;
        $user->delete();

        expect(User::query()->withoutGlobalScope('tenant')->find($userId))->toBeNull()
            ->and(User::withTrashed()->withoutGlobalScope('tenant')->find($userId))->not->toBeNull();
    });
});
