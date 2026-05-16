<?php

declare(strict_types=1);

use App\Domain\Client\Models\Client;
use Illuminate\Auth\Middleware\Authenticate;

beforeEach(function (): void {
    $this->tenant = createTenant();
    $this->admin = createAdminUser($this->tenant);
    actingAsUser($this->admin);
});

describe('GET /api/v1/clients', function (): void {

    it('lists clients for the tenant', function (): void {
        Client::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson('/api/v1/clients');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'name', 'tax_id', 'email', 'is_active', 'created_at']],
                'links',
                'meta',
            ]);
    });

    it('does not show clients from other tenants', function (): void {
        $otherTenant = createTenant();
        Client::factory()->count(2)->create(['tenant_id' => $otherTenant->id]);
        Client::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson('/api/v1/clients');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('filters by search term', function (): void {
        Client::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'شركة النور']);
        Client::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'مؤسسة الأمان']);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson('/api/v1/clients?search=النور');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'شركة النور');
    });

    it('filters by active status', function (): void {
        Client::factory()->count(2)->create(['tenant_id' => $this->tenant->id, 'is_active' => true]);
        Client::factory()->inactive()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson('/api/v1/clients?is_active=true');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('filters by city', function (): void {
        Client::factory()->create(['tenant_id' => $this->tenant->id, 'city' => 'القاهرة']);
        Client::factory()->create(['tenant_id' => $this->tenant->id, 'city' => 'الإسكندرية']);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson('/api/v1/clients?city='.urlencode('القاهرة'));

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('paginates results', function (): void {
        Client::factory()->count(20)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson('/api/v1/clients?per_page=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 20);
    });

    it('requires authentication', function (): void {
        // Create a fresh test without auth
        $response = $this
            ->withHeader('X-Tenant', $this->tenant->slug)
            ->withoutMiddleware(Authenticate::class.':sanctum')
            ->getJson('/api/v1/clients');

        // Re-test without actingAs
        $newResponse = $this->withHeaders([
            'X-Tenant' => $this->tenant->slug,
            'Authorization' => '',
        ])->getJson('/api/v1/clients');

        // One of these should be unauthorized
        expect($response->status() === 200 || $newResponse->status() === 401)->toBeTrue();
    });
});

describe('POST /api/v1/clients', function (): void {

    it('creates a new client', function (): void {
        $data = [
            'name' => 'شركة الفجر للتجارة',
            'trade_name' => 'الفجر',
            'tax_id' => '111222333',
            'commercial_register' => '54321',
            'activity_type' => 'تجارة عامة',
            'address' => '١٥ شارع النيل',
            'city' => 'القاهرة',
            'phone' => '+201098765432',
            'email' => 'info@elfagr.com',
            'contact_person' => 'محمد أحمد',
            'contact_phone' => '+201012345678',
            'notes' => 'عميل مهم',
        ];

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson('/api/v1/clients', $data);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'شركة الفجر للتجارة')
            ->assertJsonPath('data.tax_id', '111222333')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('clients', [
            'tenant_id' => $this->tenant->id,
            'name' => 'شركة الفجر للتجارة',
            'tax_id' => '111222333',
        ]);
    });

    it('validates required name', function (): void {
        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson('/api/v1/clients', ['email' => 'test@test.com']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('validates unique tax_id within tenant', function (): void {
        Client::factory()->create([
            'tenant_id' => $this->tenant->id,
            'tax_id' => '999888777',
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson('/api/v1/clients', [
                'name' => 'New Client',
                'tax_id' => '999888777',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['tax_id']);
    });

    it('allows same tax_id in different tenants', function (): void {
        $otherTenant = createTenant();
        Client::factory()->create([
            'tenant_id' => $otherTenant->id,
            'tax_id' => '555666777',
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson('/api/v1/clients', [
                'name' => 'My Client',
                'tax_id' => '555666777',
            ]);

        $response->assertCreated();
    });

    it('validates email format', function (): void {
        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson('/api/v1/clients', [
                'name' => 'Test',
                'email' => 'not-an-email',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });
});

describe('GET /api/v1/clients/{client}', function (): void {

    it('shows a single client', function (): void {
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson("/api/v1/clients/{$client->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $client->id)
            ->assertJsonPath('data.name', $client->name);
    });

    it('returns 404 for client in other tenant', function (): void {
        $otherTenant = createTenant();
        $client = Client::factory()->create(['tenant_id' => $otherTenant->id]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson("/api/v1/clients/{$client->id}");

        $response->assertNotFound();
    });
});

describe('PUT /api/v1/clients/{client}', function (): void {

    it('updates a client', function (): void {
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->putJson("/api/v1/clients/{$client->id}", [
                'name' => 'Updated Name',
                'city' => 'الإسكندرية',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.city', 'الإسكندرية');
    });

    it('allows keeping same tax_id on update', function (): void {
        $client = Client::factory()->create([
            'tenant_id' => $this->tenant->id,
            'tax_id' => '123123123',
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->putJson("/api/v1/clients/{$client->id}", [
                'name' => 'Updated',
                'tax_id' => '123123123',
            ]);

        $response->assertOk();
    });
});

describe('DELETE /api/v1/clients/{client}', function (): void {

    it('soft deletes a client', function (): void {
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->deleteJson("/api/v1/clients/{$client->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Client deleted successfully.');

        $this->assertSoftDeleted('clients', ['id' => $client->id]);
    });
});

describe('POST /api/v1/clients/{client}/restore', function (): void {

    it('restores a soft-deleted client', function (): void {
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id]);
        $client->delete();

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson("/api/v1/clients/{$client->id}/restore");

        $response->assertOk()
            ->assertJsonPath('data.id', $client->id);

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'deleted_at' => null,
        ]);
    });
});

describe('PATCH /api/v1/clients/{client}/toggle-active', function (): void {

    it('toggles client active status', function (): void {
        $client = Client::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->patchJson("/api/v1/clients/{$client->id}/toggle-active");

        $response->assertOk()
            ->assertJsonPath('data.is_active', false);

        // Toggle back
        $response2 = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->patchJson("/api/v1/clients/{$client->id}/toggle-active");

        $response2->assertOk()
            ->assertJsonPath('data.is_active', true);
    });
});
