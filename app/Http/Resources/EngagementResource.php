<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Engagement\Models\Engagement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Engagement */
class EngagementResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'fiscal_year_id' => $this->fiscal_year_id,
            'engagement_type' => $this->engagement_type?->value,
            'engagement_type_label' => $this->engagement_type?->label(),
            'engagement_type_label_ar' => $this->engagement_type?->labelAr(),
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'status_label_ar' => $this->status?->labelAr(),
            'manager_id' => $this->manager_id,
            'partner_id' => $this->partner_id,
            'planned_hours' => $this->planned_hours,
            'actual_hours' => $this->actual_hours,
            'budget_amount' => $this->budget_amount,
            'actual_amount' => $this->actual_amount,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'deadline' => $this->deadline?->toDateString(),
            'notes' => $this->notes,
            'progress' => $this->progress(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            'client' => $this->whenLoaded('client', fn () => new ClientResource($this->client)),
            'manager' => $this->whenLoaded('manager', fn () => [
                'id' => $this->manager->id,
                'name' => $this->manager->name,
            ]),
            'partner' => $this->whenLoaded('partner', fn () => [
                'id' => $this->partner->id,
                'name' => $this->partner->name,
            ]),
            'working_papers' => WorkingPaperResource::collection($this->whenLoaded('workingPapers')),
            'deliverables' => EngagementDeliverableResource::collection($this->whenLoaded('deliverables')),
        ];
    }
}
