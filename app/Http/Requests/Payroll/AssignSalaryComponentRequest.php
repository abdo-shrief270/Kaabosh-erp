<?php

declare(strict_types=1);

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignSalaryComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'salary_component_id' => [
                'required',
                'integer',
                Rule::exists('salary_components', 'id')->where('tenant_id', app('tenant.id')),
            ],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after:effective_from'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'salary_component_id.required' => 'مكون الراتب مطلوب.',
            'salary_component_id.exists' => 'مكون الراتب غير موجود.',
            'effective_from.required' => 'تاريخ بداية السريان مطلوب.',
            'effective_to.after' => 'تاريخ نهاية السريان يجب أن يكون بعد تاريخ البداية.',
        ];
    }
}
