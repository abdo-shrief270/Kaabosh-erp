<?php

declare(strict_types=1);

namespace App\Http\Requests\CostCenter;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCostCenterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $tenantId = app('tenant.id');

        return [
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('cost_centers')->where('tenant_id', $tenantId),
            ],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(['department', 'project', 'client', 'branch'])],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('cost_centers', 'id')->where('tenant_id', $tenantId),
            ],
            'manager_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
            ],
            'budget_amount' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'code.required' => 'كود مركز التكلفة مطلوب.',
            'code.unique' => 'كود مركز التكلفة مسجل بالفعل.',
            'name_ar.required' => 'اسم مركز التكلفة بالعربية مطلوب.',
            'type.required' => 'نوع مركز التكلفة مطلوب.',
            'type.in' => 'نوع مركز التكلفة غير صالح.',
            'parent_id.exists' => 'مركز التكلفة الأب غير موجود.',
            'manager_id.exists' => 'المدير المحدد غير موجود.',
            'budget_amount.min' => 'مبلغ الميزانية يجب أن يكون صفر أو أكثر.',
        ];
    }
}
