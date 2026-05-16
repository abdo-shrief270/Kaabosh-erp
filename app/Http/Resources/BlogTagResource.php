<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogTagResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => ['ar' => $this->name_ar, 'en' => $this->name_en],
            'posts_count' => $this->whenCounted('posts'),
        ];
    }
}
