<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\TimeTracking\Models\Timer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Timer */
class TimerResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'client_id' => $this->client_id,
            'task_description' => $this->task_description,
            'started_at' => $this->started_at?->toISOString(),
            'stopped_at' => $this->stopped_at?->toISOString(),
            'is_running' => $this->is_running,
            'elapsed_hours' => $this->elapsedHours(),
            'created_at' => $this->created_at?->toISOString(),

            'client' => new ClientResource($this->whenLoaded('client')),
        ];
    }
}
