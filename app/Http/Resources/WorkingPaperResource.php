<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Engagement\Models\WorkingPaper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkingPaper */
class WorkingPaperResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'engagement_id' => $this->engagement_id,
            'section' => $this->section,
            'reference_code' => $this->reference_code,
            'title_ar' => $this->title_ar,
            'title_en' => $this->title_en,
            'description' => $this->description,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'status_label_ar' => $this->status?->labelAr(),
            'assigned_to' => $this->assigned_to,
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'document_id' => $this->document_id,
            'notes' => $this->notes,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            'assigned_user' => $this->whenLoaded('assignedTo', fn () => [
                'id' => $this->assignedTo->id,
                'name' => $this->assignedTo->name,
            ]),
            'reviewer' => $this->whenLoaded('reviewedByUser', fn () => [
                'id' => $this->reviewedByUser->id,
                'name' => $this->reviewedByUser->name,
            ]),
        ];
    }
}
