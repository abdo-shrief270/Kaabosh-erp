<?php

declare(strict_types=1);

use App\Domain\Admin\Services\ImpersonationService;
use Illuminate\Auth\Access\AuthorizationException;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    $this->superAdmin = createSuperAdmin();
    $this->tenant = createTenant();
    $this->target = createAdminUser($this->tenant);
});

describe('ImpersonationService', function (): void {

    it('allows a SuperAdmin to impersonate a non-SuperAdmin', function (): void {
        actingAsUser($this->superAdmin);

        $token = app(ImpersonationService::class)
            ->impersonateUser($this->target, 'Investigating billing discrepancy on invoice 1234.');

        expect($token)->toBeString()->not->toBeEmpty();

        $activity = Activity::query()
            ->where('log_name', 'impersonation')
            ->where('description', 'user_impersonated')
            ->latest('id')
            ->first();

        expect($activity)->not->toBeNull()
            ->and($activity->causer_id)->toBe($this->superAdmin->id)
            ->and($activity->subject_id)->toBe($this->target->id)
            ->and($activity->properties['reason'])->toBe('Investigating billing discrepancy on invoice 1234.')
            ->and($activity->properties['tenant_id'])->toBe($this->target->tenant_id);
    });

    it('refuses to impersonate another SuperAdmin', function (): void {
        actingAsUser($this->superAdmin);

        $otherSuperAdmin = createSuperAdmin();

        app(ImpersonationService::class)
            ->impersonateUser($otherSuperAdmin, 'Attempting to impersonate peer admin.');
    })->throws(AuthorizationException::class, 'Refusing to impersonate another SuperAdmin.');

    it('refuses when caller is not a SuperAdmin', function (): void {
        $nonSuper = createAdminUser($this->tenant);
        actingAsUser($nonSuper);

        app(ImpersonationService::class)
            ->impersonateUser($this->target, 'Unauthorized impersonation attempt.');
    })->throws(AuthorizationException::class, 'Only SuperAdmins may impersonate users.');

    it('returns a token that authenticates on /api/v1/me', function (): void {
        actingAsUser($this->superAdmin);

        $token = app(ImpersonationService::class)
            ->impersonateUser($this->target, 'Validating token works on the API.');

        // Reset auth so we exercise the token path, not actingAs.
        auth()->forgetGuards();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/me');

        $response->assertOk()
            ->assertJsonPath('data.id', $this->target->id)
            ->assertJsonPath('data.email', $this->target->email);
    });
});
