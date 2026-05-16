<?php

declare(strict_types=1);

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->where('tenant_id', app('tenant.id')),
            ],
            'date' => ['required', 'date'],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i', 'after:check_in'],
            'status' => ['required', Rule::in(['present', 'absent', 'late', 'half_day', 'on_leave', 'holiday'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'الموظف مطلوب.',
            'employee_id.exists' => 'الموظف غير موجود.',
            'date.required' => 'التاريخ مطلوب.',
            'status.required' => 'حالة الحضور مطلوبة.',
            'check_out.after' => 'وقت الانصراف يجب أن يكون بعد وقت الحضور.',
        ];
    }
}
