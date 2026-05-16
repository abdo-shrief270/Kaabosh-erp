<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Subscription\Models\SubscriptionAddOn;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SubscriptionAddOn */
class SubscriptionAddOnResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subscription_id' => $this->subscription_id,
            'add_on_id' => $this->add_on_id,
            'quantity' => $this->quantity,
            'status' => $this->status->value,
            'billing_cycle' => $this->billing_cycle->value,
            'price' => $this->price,
            'currency' => $this->currency,
            'current_period_start' => $this->current_period_start?->toDateString(),
            'current_period_end' => $this->current_period_end?->toDateString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'cancel_at_period_end' => $this->cancel_at_period_end,
            'expires_at' => $this->expires_at?->toISOString(),
            'gateway' => $this->gateway?->value,

            // Conditional relations
            'add_on' => new AddOnResource($this->whenLoaded('addOn')),
            'credits' => $this->whenLoaded('credits', fn () => $this->credits->map(fn ($c) => [
                'id' => $c->id,
                'kind' => $c->kind,
                'quantity_total' => $c->quantity_total,
                'quantity_used' => $c->quantity_used,
                'remaining' => $c->remaining(),
                'expires_at' => $c->expires_at?->toISOString(),
            ])->values()),

            'is_active' => $this->isActive(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
