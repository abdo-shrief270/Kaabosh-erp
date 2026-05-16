<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Tenant\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Tenant */
class AdminTenantResource extends JsonResource
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
            'status' => $this->status?->value,
            'trial_ends_at' => $this->trial_ends_at?->toISOString(),
            'users_count' => $this->whenCounted('users'),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
