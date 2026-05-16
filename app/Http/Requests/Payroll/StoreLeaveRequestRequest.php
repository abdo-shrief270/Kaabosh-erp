<?php

declare(strict_types=1);

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'leave_type_id' => [
                'required',
                'integer',
                Rule::exists('leave_types', 'id')->where('tenant_id', app('tenant.id')),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'leave_type_id.required' => 'نوع الإجازة مطلوب.',
            'leave_type_id.exists' => 'نوع الإجازة غير موجود.',
            'start_date.required' => 'تاريخ بداية الإجازة مطلوب.',
            'end_date.required' => 'تاريخ نهاية الإجازة مطلوب.',
            'end_date.after_or_equal' => 'تاريخ نهاية الإجازة يجب أن يكون بعد أو يساوي تاريخ البداية.',
        ];
    }
}
