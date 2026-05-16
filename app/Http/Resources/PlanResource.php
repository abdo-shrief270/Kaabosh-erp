<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Subscription\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Plan */
class PlanResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,
            'slug' => $this->slug,
            'description_en' => $this->description_en,
            'description_ar' => $this->description_ar,
            'price_monthly' => $this->price_monthly,
            'price_annual' => $this->price_annual,
            'currency' => $this->currency,
            'trial_days' => $this->trial_days,
            'limits' => $this->limits,
            'features' => $this->features,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}
