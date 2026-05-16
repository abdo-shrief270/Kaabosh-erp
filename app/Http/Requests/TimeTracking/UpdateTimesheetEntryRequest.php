<?php

declare(strict_types=1);

namespace App\Http\Requests\TimeTracking;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTimesheetEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'date' => ['sometimes', 'date'],
            'task_description' => ['sometimes', 'string', 'max:500'],
            'hours' => ['sometimes', 'numeric', 'min:0.01', 'max:24'],
            'is_billable' => ['sometimes', 'boolean'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
