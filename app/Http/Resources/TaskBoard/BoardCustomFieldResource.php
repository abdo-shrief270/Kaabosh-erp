<?php

declare(strict_types=1);

namespace App\Http\Resources\TaskBoard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoardCustomFieldResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'board_id' => $this->board_id,
            'key' => $this->key,
            'label' => $this->label,
            'type' => $this->type,
            'options' => $this->options ?? [],
            'required' => (bool) $this->required,
            'position' => (float) $this->position,
        ];
    }
}
