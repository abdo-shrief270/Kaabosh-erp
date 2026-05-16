<?php

declare(strict_types=1);

use App\Domain\Client\Models\Client;
use App\Domain\Tenant\Models\Tenant;

describe('Client Model', function (): void {

    it('can be created with factory', function (): void {
        $client = Client::factory()->create();

        expect($client)->toBeInstanceOf(Client::class)
            ->and($client->id)->toBeInt()
            ->and($client->name)->toBeString();
    });

    it('belongs to a tenant', function (): void {
        $tenant = createTenant();
        $client = Client::factory()->create(['tenant_id' => $tenant->id]);

        expect($client->tenant->id)->toBe($tenant->id);
    });

    it('defaults to active', function (): void {
        $client = Client::factory()->create();

        expect($client->is_active)->toBeTrue();
    });

    it('can be deactivated', function (): void {
        $client = Client::factory()->inactive()->create();

        expect($client->is_active)->toBeFalse();
    });

    it('scopes active clients', function (): void {
        $tenant = createTenant();

        Client::factory()->count(2)->create(['tenant_id' => $tenant->id, 'is_active' => true]);
        Client::factory()->inactive()->create(['tenant_id' => $tenant->id]);

        // Set tenant context
        app()->instance('tenant.id', $tenant->id);

        $active = Client::query()->active()->count();

        expect($active)->toBe(2);
    });

    it('searches by name', function (): void {
        $tenant = createTenant();
        app()->instance('tenant.id', $tenant->id);

        Client::factory()->create(['tenant_id' => $tenant->id, 'name' => 'شركة النور للتجارة']);
        Client::factory()->create(['tenant_id' => $tenant->id, 'name' => 'مؤسسة الأمان']);

        $results = Client::query()->search('النور')->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('شركة النور للتجارة');
    });

    it('searches by tax_id', function (): void {
        $tenant = createTenant();
        app()->instance('tenant.id', $tenant->id);

        Client::factory()->create(['tenant_id' => $tenant->id, 'tax_id' => '123456789']);
        Client::factory()->create(['tenant_id' => $tenant->id, 'tax_id' => '987654321']);

        $results = Client::query()->search('12345')->get();

        expect($results)->toHaveCount(1);
    });

    it('searches by contact_person', function (): void {
        $tenant = createTenant();
        app()->instance('tenant.id', $tenant->id);

        Client::factory()->create(['tenant_id' => $tenant->id, 'contact_person' => 'أحمد محمد']);
        Client::factory()->create(['tenant_id' => $tenant->id, 'contact_person' => 'سارة علي']);

        $results = Client::query()->search('أحمد')->get();

        expect($results)->toHaveCount(1);
    });

    it('returns all when search is null', function (): void {
        $tenant = createTenant();
        app()->instance('tenant.id', $tenant->id);

        Client::factory()->count(3)->create(['tenant_id' => $tenant->id]);

        $results = Client::query()->search(null)->get();

        expect($results)->toHaveCount(3);
    });

    it('soft deletes', function (): void {
        $tenant = createTenant();
        app()->instance('tenant.id', $tenant->id);

        $client = Client::factory()->create(['tenant_id' => $tenant->id]);
        $client->delete();

        expect(Client::query()->count())->toBe(0)
            ->and(Client::withTrashed()->count())->toBe(1);
    });

    it('casts is_active to boolean', function (): void {
        $client = Client::factory()->create();

        expect($client->is_active)->toBeBool();
    });
});
