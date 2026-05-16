<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Client\Models\Client;
use App\Domain\Client\Services\ClientService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class ClientController extends Controller
{
    public function __construct(
        private readonly ClientService $clientService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $clients = $this->clientService->list([
            'search' => $request->query('search'),
            'is_active' => $request->has('is_active') ? filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN) : null,
            'city' => $request->query('city'),
            'sort_by' => $request->query('sort_by', 'name'),
            'sort_dir' => $request->query('sort_dir', 'asc'),
            'per_page' => min((int) ($request->query('per_page', 15)), 100),
        ]);

        return ClientResource::collection($clients);
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = $this->clientService->create($request->validated());

        return (new ClientResource($client))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Client $client): ClientResource
    {
        return new ClientResource($client);
    }

    public function update(UpdateClientRequest $request, Client $client): ClientResource
    {
        $client = $this->clientService->update($client, $request->validated());

        return new ClientResource($client);
    }

    public function destroy(Client $client): JsonResponse
    {
        $this->clientService->delete($client);

        return response()->json([
            'message' => __('messages.client.deleted'),
        ]);
    }

    public function restore(int $client): JsonResponse
    {
        $restored = $this->clientService->restore($client);

        return (new ClientResource($restored))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function toggleActive(Client $client): ClientResource
    {
        $client = $this->clientService->toggleActive($client);

        return new ClientResource($client);
    }

}
