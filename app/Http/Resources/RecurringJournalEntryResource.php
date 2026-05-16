<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecurringJournalEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'template_name_ar' => $this->template_name_ar,
            'template_name_en' => $this->template_name_en,
            'description' => $this->description,
            'frequency' => $this->frequency->value,
            'frequency_label' => $this->frequency->label(),
            'frequency_label_ar' => $this->frequency->labelAr(),
            'lines' => $this->lines,
            'next_run_date' => $this->next_run_date?->toDateString(),
            'last_run_date' => $this->last_run_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'is_active' => $this->is_active,
            'run_count' => $this->run_count,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
