<?php

declare(strict_types=1);

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLoanRequest extends FormRequest
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
            'loan_type' => ['required', Rule::in(['salary_advance', 'personal_loan', 'housing_loan'])],
            'amount' => ['required', 'numeric', 'min:1', 'max:9999999999.99'],
            'installment_amount' => ['required', 'numeric', 'min:1'],
            'start_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'الموظف مطلوب.',
            'employee_id.exists' => 'الموظف غير موجود.',
            'loan_type.required' => 'نوع القرض مطلوب.',
            'amount.required' => 'مبلغ القرض مطلوب.',
            'amount.min' => 'مبلغ القرض يجب أن يكون 1 على الأقل.',
            'installment_amount.required' => 'مبلغ القسط مطلوب.',
            'installment_amount.min' => 'مبلغ القسط يجب أن يكون 1 على الأقل.',
            'start_date.required' => 'تاريخ بداية القرض مطلوب.',
        ];
    }
}
