<?php

declare(strict_types=1);

namespace App\Http\Resources\TaskBoard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'board_id' => $this->board_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->status?->value,
            'release_date' => $this->release_date,
            'released_at' => $this->released_at,
            'color' => $this->color,
            'tasks_count' => $this->whenCounted('tasks'),
        ];
    }
}
