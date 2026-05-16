<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\TimeTracking\Models\TimesheetEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TimesheetEntry */
class TimesheetEntryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'client_id' => $this->client_id,
            'date' => $this->date?->toDateString(),
            'task_description' => $this->task_description,
            'hours' => $this->hours,
            'is_billable' => $this->is_billable,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'status_label_ar' => $this->status?->labelAr(),
            'hourly_rate' => $this->hourly_rate,
            'notes' => $this->notes,
            'invoice_id' => $this->invoice_id,
            'approved_at' => $this->approved_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'client' => new ClientResource($this->whenLoaded('client')),
            'approver' => $this->whenLoaded('approver', fn () => [
                'id' => $this->approver->id,
                'name' => $this->approver->name,
            ]),
        ];
    }
}
