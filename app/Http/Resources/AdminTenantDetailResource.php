<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Tenant\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Tenant */
class AdminTenantDetailResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'domain' => $this->domain,
            'email' => $this->email,
            'phone' => $this->phone,
            'tax_id' => $this->tax_id,
            'commercial_register' => $this->commercial_register,
            'address' => $this->address,
            'city' => $this->city,
            'status' => $this->status?->value,
            'settings' => $this->settings,
            'trial_ends_at' => $this->trial_ends_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
