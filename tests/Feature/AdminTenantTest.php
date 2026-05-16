<?php

declare(strict_types=1);

beforeEach(function (): void {
    $this->superAdmin = createSuperAdmin();
    actingAsUser($this->superAdmin);
});

describe('GET /api/v1/admin/tenants', function (): void {

    it('lists tenants with pagination', function (): void {
        createTenant();
        createTenant();

        $response = $this->getJson('/api/v1/admin/tenants');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('filters by status', function (): void {
        createTenant(['status' => 'active']);
        createTenant(['status' => 'trial']);

        $response = $this->getJson('/api/v1/admin/tenants?status=active');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('searches by name', function (): void {
        createTenant(['name' => 'شركة النيل']);
        createTenant(['name' => 'شركة الأهرام']);

        $response = $this->getJson('/api/v1/admin/tenants?search=النيل');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });
});

describe('Tenant actions', function (): void {

    it('suspends a tenant', function (): void {
        $tenant = createTenant(['status' => 'active']);

        $response = $this->postJson("/api/v1/admin/tenants/{$tenant->slug}/suspend");

        $response->assertOk()
            ->assertJsonPath('data.status', 'suspended');
    });

    it('activates a suspended tenant', function (): void {
        $tenant = createTenant(['status' => 'suspended']);

        $response = $this->postJson("/api/v1/admin/tenants/{$tenant->slug}/activate");

        $response->assertOk()
            ->assertJsonPath('data.status', 'active');
    });

    it('cancels a tenant', function (): void {
        $tenant = createTenant(['status' => 'active']);

        $response = $this->postJson("/api/v1/admin/tenants/{$tenant->slug}/cancel");

        $response->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    });

    it('generates impersonation token', function (): void {
        $tenant = createTenant();
        createAdminUser($tenant);

        $response = $this->postJson("/api/v1/admin/tenants/{$tenant->slug}/impersonate");

        $response->assertOk()
            ->assertJsonStructure(['data' => ['token']]);

        expect($response->json('data.token'))->toBeString()->not->toBeEmpty();
    });
});
