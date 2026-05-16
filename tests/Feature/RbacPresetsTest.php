<?php

declare(strict_types=1);
use App\Domain\Shared\Enums\UserRole;
use App\Models\User;

describe('GET /api/v1/rbac/role-presets', function (): void {

    it('returns the role → permission preset map for team admins', function (): void {
        $tenant = createTenant();
        $admin = createAdminUser($tenant);
        actingAsUser($admin);

        $response = $this->withHeader('X-Tenant', $tenant->slug)
            ->getJson('/api/v1/rbac/role-presets');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['role', 'label', 'label_ar', 'permissions'],
                ],
            ]);

        $roles = collect($response->json('data'))->pluck('role')->all();
        expect($roles)->toContain('admin', 'accountant', 'auditor');

        $adminPreset = collect($response->json('data'))->firstWhere('role', 'admin');
        expect($adminPreset['permissions'])->toContain('manage_team', 'manage_invoices', 'manage_engagements');
    });

    it('rejects users without manage_team permission', function (): void {
        $tenant = createTenant();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::Auditor,
        ]);
        actingAsUser($user);

        $response = $this->withHeader('X-Tenant', $tenant->slug)
            ->getJson('/api/v1/rbac/role-presets');

        $response->assertForbidden();
    });
});
