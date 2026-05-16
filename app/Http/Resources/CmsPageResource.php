<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CmsPageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => [
                'ar' => $this->title_ar,
                'en' => $this->title_en,
            ],
            'content' => [
                'ar' => $this->content_ar,
                'en' => $this->content_en,
            ],
            'meta_description' => [
                'ar' => $this->meta_description_ar,
                'en' => $this->meta_description_en,
            ],
            'is_published' => $this->is_published,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
