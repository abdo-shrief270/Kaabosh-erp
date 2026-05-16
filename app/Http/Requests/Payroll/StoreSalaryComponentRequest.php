<?php

declare(strict_types=1);

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalaryComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $componentId = $this->route('salaryComponent')?->id;

        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('salary_components', 'code')
                    ->where('tenant_id', app('tenant.id'))
                    ->ignore($componentId),
            ],
            'type' => ['required', Rule::in(['allowance', 'deduction', 'contribution'])],
            'calculation_type' => ['required', Rule::in(['fixed', 'percentage_of_basic', 'percentage_of_gross'])],
            'default_amount' => ['nullable', 'numeric', 'min:0'],
            'default_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_taxable' => ['sometimes', 'boolean'],
            'is_social_insurable' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name_ar.required' => 'اسم المكون بالعربية مطلوب.',
            'code.required' => 'كود المكون مطلوب.',
            'code.unique' => 'كود المكون مستخدم بالفعل.',
            'type.required' => 'نوع المكون مطلوب.',
            'calculation_type.required' => 'طريقة الحساب مطلوبة.',
        ];
    }
}
