<?php

declare(strict_types=1);

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $leaveTypeId = $this->route('leaveType')?->id;

        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('leave_types', 'code')
                    ->where('tenant_id', app('tenant.id'))
                    ->ignore($leaveTypeId),
            ],
            'days_per_year' => ['required', 'numeric', 'min:0.5'],
            'is_paid' => ['sometimes', 'boolean'],
            'requires_approval' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name_ar.required' => 'اسم نوع الإجازة بالعربية مطلوب.',
            'code.required' => 'كود نوع الإجازة مطلوب.',
            'code.unique' => 'كود نوع الإجازة مستخدم بالفعل.',
            'days_per_year.required' => 'عدد أيام الإجازة السنوية مطلوب.',
            'days_per_year.min' => 'عدد أيام الإجازة يجب أن يكون 0.5 يوم على الأقل.',
        ];
    }
}
