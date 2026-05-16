<?php

declare(strict_types=1);

use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Route;

describe('IdentifyTenant Middleware', function (): void {

    it('resolves tenant from X-Tenant header', function (): void {
        $tenant = Tenant::factory()->create(['slug' => 'test-firm']);
        $user = User::factory()->admin()->create(['tenant_id' => $tenant->id]);
        actingAsUser($user);

        // Register a test route with tenant middleware
        Route::middleware(['auth:sanctum', 'tenant'])
            ->get('/api/v1/test-tenant', fn () => response()->json([
                'tenant_id' => app('tenant.id'),
                'tenant_name' => app('tenant')->name,
            ]));

        $response = $this->withHeader('X-Tenant', 'test-firm')
            ->getJson('/api/v1/test-tenant');

        $response->assertOk()
            ->assertJsonPath('tenant_id', $tenant->id);
    });

    it('returns 404 for non-existent tenant', function (): void {
        $user = User::factory()->create();
        actingAsUser($user);

        Route::middleware(['auth:sanctum', 'tenant'])
            ->get('/api/v1/test-tenant-missing', fn () => response()->json(['ok' => true]));

        $response = $this->withHeader('X-Tenant', 'non-existent')
            ->getJson('/api/v1/test-tenant-missing');

        $response->assertNotFound();
    });

    it('returns 403 for suspended tenant', function (): void {
        $tenant = Tenant::factory()->suspended()->create(['slug' => 'suspended-firm']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        actingAsUser($user);

        Route::middleware(['auth:sanctum', 'tenant'])
            ->get('/api/v1/test-tenant-suspended', fn () => response()->json(['ok' => true]));

        $response = $this->withHeader('X-Tenant', 'suspended-firm')
            ->getJson('/api/v1/test-tenant-suspended');

        $response->assertForbidden();
    });
});

describe('EnsureSuperAdmin Middleware', function (): void {

    it('allows super admin access', function (): void {
        $superAdmin = createSuperAdmin();
        actingAsUser($superAdmin);

        Route::middleware(['auth:sanctum', 'super_admin'])
            ->get('/api/v1/test-super', fn () => response()->json(['ok' => true]));

        $response = $this->getJson('/api/v1/test-super');

        $response->assertOk();
    });

    it('rejects non-super-admin user', function (): void {
        $user = User::factory()->admin()->create();
        actingAsUser($user);

        Route::middleware(['auth:sanctum', 'super_admin'])
            ->get('/api/v1/test-super-reject', fn () => response()->json(['ok' => true]));

        $response = $this->getJson('/api/v1/test-super-reject');

        $response->assertForbidden();
    });
});

describe('AdminIpWhitelist Middleware', function (): void {

    it('allows all IPs when whitelist is empty', function (): void {
        config(['auth.admin_ip_whitelist' => '']);
        $superAdmin = createSuperAdmin();
        actingAsUser($superAdmin);

        Route::middleware(['auth:sanctum', 'admin.ip'])
            ->get('/api/v1/test-ip-open', fn () => response()->json(['ok' => true]));

        $this->getJson('/api/v1/test-ip-open')->assertOk();
    });

    it('denies requests from non-whitelisted IPs', function (): void {
        config(['auth.admin_ip_whitelist' => '10.0.0.1,10.0.0.2']);
        $superAdmin = createSuperAdmin();
        actingAsUser($superAdmin);

        Route::middleware(['auth:sanctum', 'admin.ip'])
            ->get('/api/v1/test-ip-denied', fn () => response()->json(['ok' => true]));

        $this->getJson('/api/v1/test-ip-denied')->assertForbidden();
    });

    it('allows requests from a whitelisted IP', function (): void {
        config(['auth.admin_ip_whitelist' => '127.0.0.1']);
        $superAdmin = createSuperAdmin();
        actingAsUser($superAdmin);

        Route::middleware(['auth:sanctum', 'admin.ip'])
            ->get('/api/v1/test-ip-allowed', fn () => response()->json(['ok' => true]));

        // Test client defaults to 127.0.0.1 for $request->ip()
        $this->getJson('/api/v1/test-ip-allowed')->assertOk();
    });

    it('allows requests inside a CIDR range', function (): void {
        config(['auth.admin_ip_whitelist' => '127.0.0.0/8']);
        $superAdmin = createSuperAdmin();
        actingAsUser($superAdmin);

        Route::middleware(['auth:sanctum', 'admin.ip'])
            ->get('/api/v1/test-ip-cidr', fn () => response()->json(['ok' => true]));

        $this->getJson('/api/v1/test-ip-cidr')->assertOk();
    });
});

describe('EnsureActiveUser Middleware', function (): void {

    it('allows active user', function (): void {
        $user = User::factory()->create(['is_active' => true]);
        actingAsUser($user);

        Route::middleware(['auth:sanctum', 'active'])
            ->get('/api/v1/test-active', fn () => response()->json(['ok' => true]));

        $response = $this->getJson('/api/v1/test-active');

        $response->assertOk();
    });

    it('rejects deactivated user', function (): void {
        $user = User::factory()->inactive()->create();
        actingAsUser($user);

        Route::middleware(['auth:sanctum', 'active'])
            ->get('/api/v1/test-inactive', fn () => response()->json(['ok' => true]));

        $response = $this->getJson('/api/v1/test-inactive');

        $response->assertForbidden();
    });
});
