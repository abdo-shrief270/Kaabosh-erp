<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Engagement\Models\EngagementDeliverable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EngagementDeliverable */
class EngagementDeliverableResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'engagement_id' => $this->engagement_id,
            'title_ar' => $this->title_ar,
            'title_en' => $this->title_en,
            'due_date' => $this->due_date?->toDateString(),
            'is_completed' => $this->is_completed,
            'completed_at' => $this->completed_at?->toISOString(),
            'completed_by' => $this->completed_by,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            'completed_by_user' => $this->whenLoaded('completedByUser', fn () => [
                'id' => $this->completedByUser->id,
                'name' => $this->completedByUser->name,
            ]),
        ];
    }
}
