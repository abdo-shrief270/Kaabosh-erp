<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => ['ar' => $this->title_ar, 'en' => $this->title_en],
            'excerpt' => ['ar' => $this->excerpt_ar, 'en' => $this->excerpt_en],
            'content' => ['ar' => $this->content_ar, 'en' => $this->content_en],
            'cover_image' => $this->cover_image,
            'meta_description' => ['ar' => $this->meta_description_ar, 'en' => $this->meta_description_en],
            'author_name' => $this->author_name,
            'is_published' => $this->is_published,
            'is_featured' => $this->is_featured,
            'published_at' => $this->published_at?->toISOString(),
            'reading_time' => $this->reading_time,
            'views_count' => $this->views_count,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'slug' => $this->category->slug,
                'name' => ['ar' => $this->category->name_ar, 'en' => $this->category->name_en],
            ]),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->map(fn ($t) => [
                'id' => $t->id,
                'slug' => $t->slug,
                'name' => ['ar' => $t->name_ar, 'en' => $t->name_en],
            ])),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
