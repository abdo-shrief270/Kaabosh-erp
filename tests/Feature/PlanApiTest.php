<?php

declare(strict_types=1);

use App\Domain\Subscription\Models\Plan;

describe('GET /api/v1/plans', function (): void {

    it('lists active plans without authentication', function (): void {
        Plan::factory()->starter()->create();
        Plan::factory()->professional()->create();
        Plan::factory()->inactive()->create();

        $response = $this->getJson('/api/v1/plans');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [[
                    'id', 'name_en', 'name_ar', 'slug', 'price_monthly',
                    'price_annual', 'currency', 'trial_days', 'limits', 'features', 'is_active',
                ]],
            ]);
    });

    it('does not return inactive plans', function (): void {
        Plan::factory()->inactive()->create();
        Plan::factory()->starter()->create();

        $response = $this->getJson('/api/v1/plans');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });
});

describe('GET /api/v1/plans/{plan}', function (): void {

    it('shows a single plan without authentication', function (): void {
        $plan = Plan::factory()->starter()->create();

        $response = $this->getJson("/api/v1/plans/{$plan->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $plan->id)
            ->assertJsonPath('data.slug', 'starter')
            ->assertJsonPath('data.name_en', 'Starter')
            ->assertJsonStructure([
                'data' => [
                    'id', 'name_en', 'name_ar', 'slug', 'price_monthly',
                    'price_annual', 'currency', 'limits', 'features',
                ],
            ]);
    });
});

describe('POST /api/v1/admin/plans', function (): void {

    it('super admin can create a plan', function (): void {
        $superAdmin = createSuperAdmin();
        actingAsUser($superAdmin);

        $data = [
            'name_en' => 'Business',
            'name_ar' => 'أعمال',
            'slug' => 'business',
            'price_monthly' => 799.00,
            'price_annual' => 7990.00,
            'trial_days' => 14,
            'limits' => [
                'max_users' => 20,
                'max_clients' => 300,
                'max_invoices_per_month' => 2000,
            ],
            'features' => [
                'e_invoice' => true,
                'api_access' => true,
                'custom_reports' => true,
            ],
        ];

        $response = $this->postJson('/api/v1/admin/plans', $data);

        $response->assertCreated()
            ->assertJsonPath('data.slug', 'business')
            ->assertJsonPath('data.name_en', 'Business')
            ->assertJsonPath('data.price_monthly', '799.00');

        $this->assertDatabaseHas('plans', ['slug' => 'business']);
    });

    it('non-admin cannot create a plan', function (): void {
        $tenant = createTenant();
        $user = createAdminUser($tenant);
        actingAsUser($user);

        $data = [
            'name_en' => 'Hacker Plan',
            'name_ar' => 'خطة مخترق',
            'slug' => 'hacker',
            'price_monthly' => 0,
            'price_annual' => 0,
            'limits' => ['max_users' => 999],
            'features' => ['all' => true],
        ];

        $response = $this->postJson('/api/v1/admin/plans', $data);

        $response->assertForbidden();
    });
});

describe('PUT /api/v1/admin/plans/{plan}', function (): void {

    it('super admin can update a plan', function (): void {
        $superAdmin = createSuperAdmin();
        actingAsUser($superAdmin);

        $plan = Plan::factory()->starter()->create();

        $response = $this->putJson("/api/v1/admin/plans/{$plan->id}", [
            'price_monthly' => 399.00,
            'name_en' => 'Starter Plus',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.price_monthly', '399.00')
            ->assertJsonPath('data.name_en', 'Starter Plus');
    });
});
