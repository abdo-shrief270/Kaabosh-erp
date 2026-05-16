<?php

declare(strict_types=1);

use App\Domain\Webhook\Models\WebhookDelivery;
use App\Domain\Webhook\Models\WebhookEndpoint;
use App\Domain\Webhook\Services\WebhookService;

describe('Webhook Endpoints', function (): void {

    it('creates a webhook endpoint', function (): void {
        $user = createAdminUser();
        actingAsUser($user);

        $response = $this->withHeader('X-Tenant', (string) $user->tenant_id)
            ->postJson('/api/v1/webhooks', [
                'url' => 'https://example.com/webhook',
                'events' => ['invoice.created', 'payment.received'],
            ]);

        $response->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'url', 'secret', 'events']]);

        $this->assertDatabaseHas('webhook_endpoints', [
            'tenant_id' => $user->tenant_id,
            'url' => 'https://example.com/webhook',
        ]);
    });

    it('validates webhook URL', function (): void {
        $user = createAdminUser();
        actingAsUser($user);

        $response = $this->withHeader('X-Tenant', (string) $user->tenant_id)
            ->postJson('/api/v1/webhooks', [
                'url' => 'not-a-url',
                'events' => ['invoice.created'],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['url']);
    });

    it('validates event names', function (): void {
        $user = createAdminUser();
        actingAsUser($user);

        $response = $this->withHeader('X-Tenant', (string) $user->tenant_id)
            ->postJson('/api/v1/webhooks', [
                'url' => 'https://example.com/hook',
                'events' => ['invalid.event'],
            ]);

        $response->assertUnprocessable();
    });

    it('lists available webhook events', function (): void {
        $user = createAdminUser();
        actingAsUser($user);

        $response = $this->withHeader('X-Tenant', (string) $user->tenant_id)
            ->getJson('/api/v1/webhooks/events');

        $response->assertOk()
            ->assertJsonStructure(['data']);

        expect($response->json('data'))->toContain('invoice.created')
            ->toContain('payment.received');
    });
});

describe('Webhook Dispatch', function (): void {

    it('creates delivery records when dispatching events', function (): void {
        $tenant = createTenant();

        WebhookEndpoint::create([
            'tenant_id' => $tenant->id,
            'url' => 'https://example.com/hook',
            'secret' => 'test-secret-key-12345',
            'events' => ['invoice.created'],
            'is_active' => true,
        ]);

        WebhookService::dispatch($tenant->id, 'invoice.created', [
            'invoice_id' => 123,
            'total' => 1500,
        ]);

        // In sync queue mode, job runs immediately — delivery record exists regardless of outcome
        $this->assertDatabaseHas('webhook_deliveries', [
            'event' => 'invoice.created',
        ]);

        expect(WebhookDelivery::where('event', 'invoice.created')->count())->toBe(1);
    });

    it('does not dispatch to inactive endpoints', function (): void {
        $tenant = createTenant();

        WebhookEndpoint::create([
            'tenant_id' => $tenant->id,
            'url' => 'https://example.com/hook',
            'secret' => 'test-secret',
            'events' => ['invoice.created'],
            'is_active' => false,
        ]);

        WebhookService::dispatch($tenant->id, 'invoice.created', ['id' => 1]);

        $this->assertDatabaseCount('webhook_deliveries', 0);
    });

    it('does not dispatch for non-matching events', function (): void {
        $tenant = createTenant();

        WebhookEndpoint::create([
            'tenant_id' => $tenant->id,
            'url' => 'https://example.com/hook',
            'secret' => 'test-secret',
            'events' => ['payment.received'],
            'is_active' => true,
        ]);

        WebhookService::dispatch($tenant->id, 'invoice.created', ['id' => 1]);

        $this->assertDatabaseCount('webhook_deliveries', 0);
    });

    it('dispatches to wildcard listeners', function (): void {
        $tenant = createTenant();

        WebhookEndpoint::create([
            'tenant_id' => $tenant->id,
            'url' => 'https://example.com/all',
            'secret' => 'test-secret',
            'events' => ['*'],
            'is_active' => true,
        ]);

        WebhookService::dispatch($tenant->id, 'invoice.created', ['id' => 1]);

        $this->assertDatabaseHas('webhook_deliveries', ['event' => 'invoice.created']);
    });
});

describe('Webhook Endpoint Model', function (): void {

    it('auto-disables after 50 failures', function (): void {
        $tenant = createTenant();

        $endpoint = WebhookEndpoint::create([
            'tenant_id' => $tenant->id,
            'url' => 'https://down.example.com',
            'secret' => 'test',
            'events' => ['*'],
            'is_active' => true,
            'failure_count' => 49,
        ]);

        $endpoint->recordFailure();

        expect($endpoint->fresh()->is_active)->toBeFalse()
            ->and($endpoint->fresh()->failure_count)->toBe(50);
    });

    it('resets failures on success', function (): void {
        $tenant = createTenant();

        $endpoint = WebhookEndpoint::create([
            'tenant_id' => $tenant->id,
            'url' => 'https://example.com',
            'secret' => 'test',
            'events' => ['*'],
            'is_active' => true,
            'failure_count' => 10,
        ]);

        $endpoint->resetFailures();

        expect($endpoint->fresh()->failure_count)->toBe(0);
    });
});
