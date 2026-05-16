<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Webhook\Models\WebhookEndpoint;
use App\Domain\Webhook\Services\WebhookService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tenant-level webhook endpoint management.
 * Requires tenant context (auth + tenant middleware).
 */
class WebhookEndpointController extends Controller
{
    public function __construct(
        private readonly WebhookService $webhookService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.id');
        $endpoints = $this->webhookService->listEndpoints($tenantId);

        return response()->json($endpoints);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'url' => 'required|url|max:500',
            'events' => 'required|array|min:1',
            'events.*' => 'string|in:'.implode(',', WebhookService::EVENTS).',*',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $endpoint = $this->webhookService->createEndpoint(app('tenant.id'), $data);

        return response()->json([
            'data' => $endpoint,
            'message' => 'Webhook endpoint created. Store the secret securely — it will not be shown again.',
        ], Response::HTTP_CREATED);
    }

    public function update(Request $request, WebhookEndpoint $webhookEndpoint): JsonResponse
    {
        $data = $request->validate([
            'url' => 'nullable|url|max:500',
            'events' => 'nullable|array|min:1',
            'events.*' => 'string|in:'.implode(',', WebhookService::EVENTS).',*',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'regenerate_secret' => 'boolean',
        ]);

        $endpoint = $this->webhookService->updateEndpoint($webhookEndpoint, $data);

        return response()->json(['data' => $endpoint]);
    }

    public function destroy(WebhookEndpoint $webhookEndpoint): JsonResponse
    {
        $webhookEndpoint->delete();

        return response()->json(['message' => 'Webhook endpoint deleted.']);
    }

    public function deliveries(WebhookEndpoint $webhookEndpoint): JsonResponse
    {
        $deliveries = $this->webhookService->getDeliveries($webhookEndpoint);

        return response()->json($deliveries);
    }

    public function events(): JsonResponse
    {
        return response()->json(['data' => WebhookService::EVENTS]);
    }
}
