<?php

declare(strict_types=1);

namespace App\Http\Resources\TaskBoard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoardColumnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'board_id' => $this->board_id,
            'name' => $this->name,
            'color' => $this->color,
            'position' => $this->position,
            'wip_limit' => $this->wip_limit,
            'enforce_wip' => (bool) $this->enforce_wip,
            'requires_approval' => (bool) $this->requires_approval,
            'approver_user_ids' => $this->approver_user_ids ?? [],
            'is_done' => $this->is_done,
            'is_initial' => $this->is_initial,
            'tasks_count' => $this->whenCounted('tasks'),
            'tasks' => TaskResource::collection($this->whenLoaded('tasks')),
        ];
    }
}
