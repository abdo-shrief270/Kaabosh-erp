<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Client\Models\ClientProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ClientProduct */
class ClientProductResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            // Eager-loaded by the catalog list endpoint; null on per-client
            // routes where the client is implicit from the URL.
            'client' => $this->whenLoaded('client', fn () => [
                'id' => $this->client->id,
                'name' => $this->client->name,
            ]),
            'name' => $this->name,
            'description' => $this->description,
            'unit_price' => (float) $this->unit_price,
            'default_vat_rate' => $this->default_vat_rate !== null
                ? (float) $this->default_vat_rate
                : null,
            'default_account_id' => $this->default_account_id,
            'default_account' => $this->whenLoaded('defaultAccount', fn () => $this->defaultAccount ? [
                'id' => $this->defaultAccount->id,
                'code' => $this->defaultAccount->code,
                'name_ar' => $this->defaultAccount->name_ar,
                'name_en' => $this->defaultAccount->name_en,
            ] : null),
            'is_active' => (bool) $this->is_active,
            'last_used_at' => $this->last_used_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
