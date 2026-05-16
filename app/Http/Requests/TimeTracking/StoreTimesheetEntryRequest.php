<?php

declare(strict_types=1);

namespace App\Http\Requests\TimeTracking;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTimesheetEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'client_id' => ['nullable', 'integer', Rule::exists('clients', 'id')->where('tenant_id', app('tenant.id'))],
            'date' => ['required', 'date'],
            'task_description' => ['required', 'string', 'max:500'],
            'hours' => ['required', 'numeric', 'min:0.01', 'max:24'],
            'is_billable' => ['sometimes', 'boolean'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'date.required' => 'التاريخ مطلوب.',
            'task_description.required' => 'وصف المهمة مطلوب.',
            'hours.required' => 'عدد الساعات مطلوب.',
            'hours.min' => 'عدد الساعات يجب أن يكون أكبر من صفر.',
            'hours.max' => 'عدد الساعات لا يمكن أن يتجاوز 24 ساعة.',
        ];
    }
}
