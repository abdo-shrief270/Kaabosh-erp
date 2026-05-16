<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecurringInvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client' => $this->whenLoaded('client', fn () => [
                'id' => $this->client->id,
                'name' => $this->client->name,
            ]),
            'frequency' => $this->frequency,
            'day_of_month' => $this->day_of_month,
            'day_of_week' => $this->day_of_week,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'next_run_date' => $this->next_run_date?->toDateString(),
            'last_run_date' => $this->last_run_date?->toDateString(),
            'line_items' => $this->line_items,
            'currency' => $this->currency,
            'notes' => $this->notes,
            'terms' => $this->terms,
            'due_days' => $this->due_days,
            'is_active' => $this->is_active,
            'auto_send' => $this->auto_send,
            'invoices_generated' => $this->invoices_generated,
            'max_occurrences' => $this->max_occurrences,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
