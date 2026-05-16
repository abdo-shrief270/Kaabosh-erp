<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Subscription\Models\AddOn;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AddOn */
class AddOnResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,
            'description_en' => $this->description_en,
            'description_ar' => $this->description_ar,
            'type' => $this->type->value,
            'billing_cycle' => $this->billing_cycle->value,
            'boost' => $this->boost,
            'feature_slug' => $this->feature_slug,
            'credit_kind' => $this->credit_kind,
            'credit_quantity' => $this->credit_quantity,
            'price_monthly' => $this->price_monthly,
            'price_annual' => $this->price_annual,
            'price_once' => $this->price_once,
            'currency' => $this->currency,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}
