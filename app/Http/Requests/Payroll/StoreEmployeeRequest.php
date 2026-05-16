<?php

declare(strict_types=1);

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('tenant_id', app('tenant.id')),
                Rule::unique('employees', 'user_id')->where('tenant_id', app('tenant.id')),
            ],
            'hire_date' => ['required', 'date'],
            'department' => ['nullable', 'string', 'max:100'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'base_salary' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'social_insurance_number' => ['nullable', 'string', 'max:50'],
            'bank_account' => ['nullable', 'string', 'max:100'],
            'is_insured' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'user_id.required' => 'المستخدم مطلوب.',
            'user_id.unique' => 'يوجد سجل موظف بالفعل لهذا المستخدم.',
            'hire_date.required' => 'تاريخ التعيين مطلوب.',
            'base_salary.required' => 'الراتب الأساسي مطلوب.',
            'base_salary.min' => 'الراتب الأساسي يجب أن يكون صفر أو أكثر.',
        ];
    }
}
