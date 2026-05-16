<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Subscription\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Subscription */
class SubscriptionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plan_id' => $this->plan_id,
            'status' => $this->status?->value,
            'billing_cycle' => $this->billing_cycle,
            'price' => $this->price,
            'currency' => $this->currency,
            'trial_ends_at' => $this->trial_ends_at?->toISOString(),
            'current_period_start' => $this->current_period_start?->toDateString(),
            'current_period_end' => $this->current_period_end?->toDateString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'gateway' => $this->gateway?->value,
            'created_at' => $this->created_at?->toISOString(),

            // Conditional relation
            'plan' => new PlanResource($this->whenLoaded('plan')),

            // Computed fields
            'is_accessible' => $this->isAccessible(),
            'trial_days_remaining' => $this->trialDaysRemaining(),
            'days_until_renewal' => $this->daysUntilRenewal(),
        ];
    }
}
