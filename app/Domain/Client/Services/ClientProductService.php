<?php

declare(strict_types=1);

namespace App\Domain\Client\Services;

use App\Domain\Client\Models\Client;
use App\Domain\Client\Models\ClientProduct;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class ClientProductService
{
    /**
     * List products for a single client (per-client view, used on the client
     * detail page's Products tab and as the invoice-line picker source).
     *
     * @param  array<string, mixed>  $params
     */
    public function listForClient(Client $client, array $params = []): LengthAwarePaginator
    {
        // Query directly off the model rather than the HasMany relation —
        // applyFilters expects an Eloquent\Builder and HasMany doesn't pass
        // PHP's strict typehint check even though it composes one.
        $query = ClientProduct::query()
            ->with('defaultAccount:id,code,name_ar,name_en')
            ->where('client_id', $client->id);

        return $this->applyFilters($query, $params)->paginate(
            perPage: min((int) ($params['per_page'] ?? 25), 100),
        );
    }

    /**
     * Tenant-wide catalog list — flattens products across all clients in
     * the tenant. Eager-loads client so the list page can show the client
     * name without N+1.
     *
     * @param  array<string, mixed>  $params
     */
    public function listCatalog(array $params = []): LengthAwarePaginator
    {
        $query = ClientProduct::query()
            ->with(['client:id,name', 'defaultAccount:id,code,name_ar,name_en']);

        if (! empty($params['client_id'])) {
            $query->where('client_id', (int) $params['client_id']);
        }

        return $this->applyFilters($query, $params)->paginate(
            perPage: min((int) ($params['per_page'] ?? 25), 100),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Client $client, array $data): ClientProduct
    {
        // Refresh after insert so DB defaults (e.g. `is_active = true` from
        // the migration) are reflected in the response — without this the
        // resource serializes the in-memory model with `is_active` unset,
        // which the boolean cast renders as `false`. Eager-load the account
        // so the response carries the picker-ready ref straight away.
        return $client->products()
            ->create($data + ['tenant_id' => app('tenant.id')])
            ->refresh()
            ->load('defaultAccount:id,code,name_ar,name_en');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ClientProduct $product, array $data): ClientProduct
    {
        // Prevent the unique constraint on (tenant, client, name) from
        // surfacing as a generic 500. We pre-flight the rename.
        if (
            isset($data['name'])
            && $data['name'] !== $product->name
            && ClientProduct::query()
                ->where('client_id', $product->client_id)
                ->where('name', $data['name'])
                ->whereKeyNot($product->id)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'name' => [__('messages.error.duplicate_name')],
            ]);
        }

        $product->fill($data)->save();

        return $product->load('defaultAccount:id,code,name_ar,name_en');
    }

    public function delete(ClientProduct $product): void
    {
        $product->delete();
    }

    /**
     * Bump `last_used_at` whenever this product appears on a freshly-created
     * invoice line. Called from the invoice-line observer (we'll wire that
     * up alongside the line picker; no-op until then).
     */
    public function touchLastUsed(ClientProduct $product): void
    {
        $product->forceFill(['last_used_at' => now()])->saveQuietly();
    }

    /**
     * @param  array<string, mixed>  $params
     * @return Builder<ClientProduct>
     */
    private function applyFilters(Builder $query, array $params): Builder
    {
        if (! empty($params['search'])) {
            $term = '%'.$params['search'].'%';
            $query->where(function (Builder $q) use ($term): void {
                $q->where('name', 'like', $term)
                  ->orWhere('description', 'like', $term);
            });
        }

        if (array_key_exists('is_active', $params) && $params['is_active'] !== null) {
            $query->where('is_active', (bool) $params['is_active']);
        }

        $sortBy = in_array(
            $params['sort_by'] ?? null,
            ['name', 'unit_price', 'last_used_at', 'created_at'],
            true,
        ) ? $params['sort_by'] : 'name';

        $sortDir = ($params['sort_dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        // Same NULLS-LAST treatment as VendorProductService — Postgres
        // would otherwise put never-used items at the top of the picker
        // when ordering by last_used_at desc.
        if ($sortBy === 'last_used_at') {
            $query->orderByRaw('last_used_at '.$sortDir.' NULLS LAST')
                  ->orderBy('name');
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        return $query;
    }
}
