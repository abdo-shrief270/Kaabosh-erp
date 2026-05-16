<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => ['ar' => $this->name_ar, 'en' => $this->name_en],
            'description' => ['ar' => $this->description_ar, 'en' => $this->description_en],
            'sort_order' => $this->sort_order,
            'posts_count' => $this->whenCounted('posts'),
        ];
    }
}
