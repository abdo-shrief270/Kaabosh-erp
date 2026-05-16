<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestimonialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => [
                'ar' => $this->name_ar,
                'en' => $this->name_en,
            ],
            'role' => [
                'ar' => $this->role_ar,
                'en' => $this->role_en,
            ],
            'quote' => [
                'ar' => $this->quote_ar,
                'en' => $this->quote_en,
            ],
            'rating' => $this->rating,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
