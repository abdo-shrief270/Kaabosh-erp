<?php

declare(strict_types=1);

use App\Domain\Shared\Models\FeatureFlag;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

describe('Registration', function (): void {

    it('registers a new tenant with admin user', function (): void {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'أحمد محمد',
            'email' => 'ahmed@test.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'phone' => '+201234567890',
            'tenant_name' => 'شركة الحسابات',
            'tenant_slug' => 'el-hesabat',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email', 'role'],
                    'tenant' => ['id', 'name', 'slug', 'status', 'trial_ends_at'],
                    'token',
                ],
            ]);

        expect($response->json('data.user.role'))->toBe('admin')
            ->and($response->json('data.tenant.status'))->toBe('trial')
            ->and($response->json('data.tenant.slug'))->toBe('el-hesabat');

        $this->assertDatabaseHas('tenants', ['slug' => 'el-hesabat']);
        $this->assertDatabaseHas('users', ['email' => 'ahmed@test.com', 'role' => 'admin']);
    });

    it('validates required fields', function (): void {
        $response = $this->postJson('/api/v1/register', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password', 'tenant_name', 'tenant_slug']);
    });

    it('prevents duplicate email', function (): void {
        User::factory()->create(['email' => 'taken@test.com']);

        $response = $this->postJson('/api/v1/register', [
            'name' => 'Test',
            'email' => 'taken@test.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'tenant_name' => 'Test Firm',
            'tenant_slug' => 'test-firm',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('prevents duplicate tenant slug', function (): void {
        Tenant::factory()->create(['slug' => 'taken-slug']);

        $response = $this->postJson('/api/v1/register', [
            'name' => 'Test',
            'email' => 'new@test.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'tenant_name' => 'Test Firm',
            'tenant_slug' => 'taken-slug',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['tenant_slug']);
    });
});

describe('Login', function (): void {

    it('logs in with valid credentials', function (): void {
        $tenant = Tenant::factory()->create();
        User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'user@test.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'user@test.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email', 'role', 'tenant_id'],
                    'token',
                ],
            ]);
    });

    it('rejects invalid credentials', function (): void {
        User::factory()->create([
            'email' => 'user@test.com',
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'user@test.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('rejects deactivated user', function (): void {
        User::factory()->inactive()->create([
            'email' => 'inactive@test.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'inactive@test.com',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable();
    });

    it('rejects user of suspended tenant', function (): void {
        $tenant = Tenant::factory()->suspended()->create();
        User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'suspended@test.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'suspended@test.com',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable();
    });

    it('allows super admin login without tenant', function (): void {
        User::factory()->superAdmin()->create([
            'email' => 'super@muhasebi.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'super@muhasebi.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.role', 'super_admin');
    });

    it('records last login timestamp', function (): void {
        $user = User::factory()->create([
            'email' => 'login@test.com',
            'password' => bcrypt('password123'),
            'last_login_at' => null,
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'login@test.com',
            'password' => 'password123',
        ]);

        $user->refresh();
        expect($user->last_login_at)->not->toBeNull();
    });

    it('returns requires_2fa=true for admin without 2FA enabled', function (): void {
        $tenant = Tenant::factory()->create();
        User::factory()->admin()->create([
            'tenant_id' => $tenant->id,
            'email' => 'admin-no-2fa@test.com',
            'password' => bcrypt('password123'),
            'two_factor_enabled' => false,
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'admin-no-2fa@test.com',
            'password' => 'password123',
        ]);

        $response->assertOk()->assertJsonPath('data.requires_2fa', true);
    });

    it('returns requires_2fa=false for admin with 2FA already enabled', function (): void {
        $tenant = Tenant::factory()->create();
        User::factory()->admin()->create([
            'tenant_id' => $tenant->id,
            'email' => 'admin-2fa@test.com',
            'password' => bcrypt('password123'),
            'two_factor_enabled' => true,
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'admin-2fa@test.com',
            'password' => 'password123',
        ]);

        $response->assertOk()->assertJsonPath('data.requires_2fa', false);
    });

    it('returns requires_2fa=false for non-admin users regardless of 2FA state', function (): void {
        $tenant = Tenant::factory()->create();
        User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'user@test.com',
            'password' => bcrypt('password123'),
            'two_factor_enabled' => false,
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'user@test.com',
            'password' => 'password123',
        ]);

        $response->assertOk()->assertJsonPath('data.requires_2fa', false);
    });
});

describe('Logout', function (): void {

    it('logs out authenticated user', function (): void {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/logout');

        $response->assertOk()
            ->assertJsonPath('message', 'Logged out successfully.');

        // Token should be revoked
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    });

    it('rejects unauthenticated logout', function (): void {
        $response = $this->postJson('/api/v1/logout');

        $response->assertUnauthorized();
    });
});

describe('Me (Profile)', function (): void {

    it('returns authenticated user profile', function (): void {
        $user = User::factory()->admin()->create();
        actingAsUser($user);

        $response = $this->getJson('/api/v1/me');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email', 'phone', 'role', 'locale', 'tenant_id'],
            ])
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.role', 'admin');
    });

    it('returns tenant.features map with merged flag state', function (): void {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->admin()->create(['tenant_id' => $tenant->id]);
        actingAsUser($user);

        FeatureFlag::create([
            'key' => 'custom_reports',
            'name' => 'Custom Reports',
            'is_enabled_globally' => true,
        ]);
        FeatureFlag::create([
            'key' => 'experimental_ai',
            'name' => 'Experimental AI',
            'is_enabled_globally' => false,
        ]);
        Cache::flush();

        $response = $this->getJson('/api/v1/me');

        $response->assertOk();
        $features = $response->json('data.tenant.features');
        expect($features)->toBeArray()
            ->and($features['custom_reports'] ?? null)->toBeTrue()
            ->and($features['experimental_ai'] ?? null)->toBeFalse();
    });

    it('rejects unauthenticated request', function (): void {
        $response = $this->getJson('/api/v1/me');

        $response->assertUnauthorized();
    });
});
