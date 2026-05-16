<?php

declare(strict_types=1);

use App\Domain\Shared\Enums\UserRole;
use App\Models\User;

beforeEach(function (): void {
    $this->tenant = createTenant();
    $this->admin = createAdminUser($this->tenant);
    actingAsUser($this->admin);
});

describe('GET /api/v1/team', function (): void {

    it('lists team members', function (): void {
        User::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'role' => UserRole::Accountant,
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson('/api/v1/team');

        // admin + 3 accountants = 4
        $response->assertOk()
            ->assertJsonCount(4, 'data');
    });

    it('does not show users from other tenants', function (): void {
        $otherTenant = createTenant();
        User::factory()->create(['tenant_id' => $otherTenant->id]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson('/api/v1/team');

        $response->assertOk()
            ->assertJsonCount(1, 'data'); // only admin
    });
});

describe('POST /api/v1/team/invite', function (): void {

    it('invites a new team member', function (): void {
        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson('/api/v1/team/invite', [
                'name' => 'أحمد محمد',
                'email' => 'ahmed@example.com',
                'role' => 'accountant',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'أحمد محمد')
            ->assertJsonPath('data.email', 'ahmed@example.com')
            ->assertJsonPath('data.role', 'accountant');
    });

    it('rejects duplicate email', function (): void {
        $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson('/api/v1/team/invite', [
                'name' => 'Test',
                'email' => $this->admin->email,
                'role' => 'accountant',
            ])
            ->assertUnprocessable();
    });

    it('validates required fields', function (): void {
        $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson('/api/v1/team/invite', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email']);
    });
});

describe('PATCH /api/v1/team/{user}/toggle-active', function (): void {

    it('toggles team member active status', function (): void {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->patchJson("/api/v1/team/{$user->id}/toggle-active");

        $response->assertOk()
            ->assertJsonPath('data.is_active', false);
    });

    it('prevents self-deactivation', function (): void {
        $this->withHeader('X-Tenant', $this->tenant->slug)
            ->patchJson("/api/v1/team/{$this->admin->id}/toggle-active")
            ->assertUnprocessable();
    });
});

describe('DELETE /api/v1/team/{user}', function (): void {

    it('removes a team member', function (): void {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => UserRole::Accountant,
        ]);

        $this->withHeader('X-Tenant', $this->tenant->slug)
            ->deleteJson("/api/v1/team/{$user->id}")
            ->assertOk();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    });

    it('prevents self-removal', function (): void {
        $this->withHeader('X-Tenant', $this->tenant->slug)
            ->deleteJson("/api/v1/team/{$this->admin->id}")
            ->assertUnprocessable();
    });
});
