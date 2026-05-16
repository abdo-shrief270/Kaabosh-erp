<?php

declare(strict_types=1);

use App\Domain\Auth\Services\TwoFactorService;
use App\Models\User;

describe('Two-Factor Authentication', function (): void {

    it('enables 2FA and returns secret and recovery codes', function (): void {
        // superAdmin() factory state defaults two_factor_enabled=true; override
        // it here so we're testing the first-time enable flow.
        $user = User::factory()->superAdmin()->create(['two_factor_enabled' => false]);
        actingAsUser($user);

        $response = $this->postJson('/api/v1/2fa/enable');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['secret', 'qr_uri', 'recovery_codes']])
            ->assertJsonCount(8, 'data.recovery_codes');

        expect($user->fresh()->two_factor_enabled)->toBeTrue();
    });

    it('returns 2FA status', function (): void {
        $user = User::factory()->superAdmin()->create(['two_factor_enabled' => false]);
        actingAsUser($user);

        $response = $this->getJson('/api/v1/2fa/status');

        $response->assertOk()
            ->assertJsonPath('data.enabled', false);
    });

    it('prevents enabling 2FA twice', function (): void {
        $user = User::factory()->superAdmin()->create(['two_factor_enabled' => false]);
        actingAsUser($user);

        $this->postJson('/api/v1/2fa/enable')->assertOk();
        $this->postJson('/api/v1/2fa/enable')->assertUnprocessable();
    });

    it('verifies a valid TOTP code', function (): void {
        $user = createSuperAdmin();
        actingAsUser($user);

        $result = TwoFactorService::enable($user);
        $secret = $result['secret'];

        // Generate current code
        $time = (int) floor(time() / 30);
        $code = TwoFactorService::verify($user->fresh(), 'invalid_code');

        expect($code)->toBeFalse();
    });

    it('verifies a recovery code and consumes it', function (): void {
        $user = createSuperAdmin();
        $result = TwoFactorService::enable($user);
        $recoveryCodes = $result['recovery_codes'];

        actingAsUser($user->fresh());

        $response = $this->postJson('/api/v1/2fa/verify', [
            'code' => $recoveryCodes[0],
        ]);

        $response->assertOk();

        // Same recovery code should not work again
        $response2 = $this->postJson('/api/v1/2fa/verify', [
            'code' => $recoveryCodes[0],
        ]);

        $response2->assertUnprocessable();
    });

    it('disables 2FA with correct password', function (): void {
        $user = createSuperAdmin();
        TwoFactorService::enable($user);
        actingAsUser($user->fresh());

        $response = $this->postJson('/api/v1/2fa/disable', [
            'password' => 'password', // factory default
        ]);

        $response->assertOk();
        expect($user->fresh()->two_factor_enabled)->toBeFalse();
    });

    it('rejects 2FA disable with wrong password', function (): void {
        $user = createSuperAdmin();
        TwoFactorService::enable($user);
        actingAsUser($user->fresh());

        $response = $this->postJson('/api/v1/2fa/disable', [
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable();
    });

    it('stores recovery codes hashed, not in plaintext', function (): void {
        $user = createSuperAdmin();
        $result = TwoFactorService::enable($user);
        $plaintextCodes = $result['recovery_codes'];

        $stored = json_decode(decrypt($user->fresh()->two_factor_recovery_codes), true);

        expect($stored)->toBeArray();
        expect(count($stored))->toBe(count($plaintextCodes));
        foreach ($stored as $entry) {
            expect($entry)->toStartWith('$2y$');
            expect($plaintextCodes)->not->toContain($entry);
        }
    });

    it('rejects an invalid recovery code without consuming any', function (): void {
        $user = createSuperAdmin();
        $result = TwoFactorService::enable($user);
        actingAsUser($user->fresh());

        $storedBefore = $user->fresh()->two_factor_recovery_codes;

        $response = $this->postJson('/api/v1/2fa/verify', [
            'code' => 'not-a-real-recovery-code',
        ]);

        $response->assertUnprocessable();
        expect($user->fresh()->two_factor_recovery_codes)->toBe($storedBefore);
    });
});
