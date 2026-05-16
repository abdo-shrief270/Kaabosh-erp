<?php

declare(strict_types=1);

namespace App\Domain\Client\Services;

use App\Domain\Client\Models\Client;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ClientService
{
    /**
     * List clients with search, filter, and pagination.
     *
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return Client::query()
            ->search($filters['search'] ?? null)
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->when(isset($filters['city']), fn ($q) => $q->where('city', $filters['city']))
            ->orderBy($filters['sort_by'] ?? 'name', $filters['sort_dir'] ?? 'asc')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Create a new client.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Client
    {
        return Client::query()->create($data);
    }

    /**
     * Update an existing client.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Client $client, array $data): Client
    {
        $client->update($data);

        return $client->refresh();
    }

    /**
     * Soft-delete a client.
     */
    public function delete(Client $client): void
    {
        $client->delete();
    }

    /**
     * Restore a soft-deleted client.
     */
    public function restore(int $clientId): Client
    {
        $client = Client::withTrashed()->findOrFail($clientId);
        $client->restore();

        return $client;
    }

    /**
     * Toggle active status.
     */
    public function toggleActive(Client $client): Client
    {
        $client->update(['is_active' => ! $client->is_active]);

        return $client->refresh();
    }
}
