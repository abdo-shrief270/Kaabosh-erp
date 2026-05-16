<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FaqResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question' => [
                'ar' => $this->question_ar,
                'en' => $this->question_en,
            ],
            'answer' => [
                'ar' => $this->answer_ar,
                'en' => $this->answer_en,
            ],
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
