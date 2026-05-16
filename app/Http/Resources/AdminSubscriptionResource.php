<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Subscription\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Subscription */
class AdminSubscriptionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'plan_id' => $this->plan_id,
            'status' => $this->status?->value,
            'billing_cycle' => $this->billing_cycle,
            'price' => $this->price,
            'currency' => $this->currency,
            'trial_ends_at' => $this->trial_ends_at?->toISOString(),
            'current_period_start' => $this->current_period_start?->toISOString(),
            'current_period_end' => $this->current_period_end?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),

            'tenant' => new AdminTenantResource($this->whenLoaded('tenant')),
            'plan' => $this->whenLoaded('plan', fn () => [
                'id' => $this->plan->id,
                'name_ar' => $this->plan->name_ar,
                'name_en' => $this->plan->name_en,
            ]),
        ];
    }
}
