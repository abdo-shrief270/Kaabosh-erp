<?php

declare(strict_types=1);

beforeEach(function (): void {
    $this->superAdmin = createSuperAdmin();
    actingAsUser($this->superAdmin);
});

describe('GET /api/v1/admin/dashboard', function (): void {

    it('returns dashboard KPIs for super admin', function (): void {
        createTenant(['status' => 'active']);
        createTenant(['status' => 'trial']);

        $response = $this->getJson('/api/v1/admin/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'tenants' => ['total', 'active', 'trial', 'suspended', 'cancelled'],
                    'revenue' => ['mrr', 'arr', 'total_collected'],
                    'new_signups_this_month',
                    'churn_rate',
                    'subscriptions_by_plan',
                ],
            ]);
    });

    it('returns 403 for non-super-admin', function (): void {
        $tenant = createTenant();
        $admin = createAdminUser($tenant);
        actingAsUser($admin);

        $this->withHeader('X-Tenant', $tenant->slug)
            ->getJson('/api/v1/admin/dashboard')
            ->assertForbidden();
    });
});

describe('GET /api/v1/admin/revenue/monthly', function (): void {

    it('returns monthly revenue data', function (): void {
        $response = $this->getJson('/api/v1/admin/revenue/monthly');

        $response->assertOk()
            ->assertJsonStructure(['data']);
    });
});

describe('GET /api/v1/admin/revenue/by-plan', function (): void {

    it('returns revenue by plan', function (): void {
        $response = $this->getJson('/api/v1/admin/revenue/by-plan');

        $response->assertOk()
            ->assertJsonStructure(['data']);
    });
});
