<?php

declare(strict_types=1);

namespace App\Http\Requests\Expenses;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $tenantId = app('tenant.id');
        $categoryId = $this->route('expense_category')?->id;

        return [
            'name_ar' => ['sometimes', 'required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'code' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
                Rule::unique('expense_categories')->where('tenant_id', $tenantId)->ignore($categoryId),
            ],
            'account_id' => [
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')->where('tenant_id', $tenantId),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name_ar.required' => 'اسم فئة المصروف بالعربية مطلوب.',
            'code.unique' => 'كود الفئة مسجل بالفعل.',
            'account_id.exists' => 'الحساب المحدد غير موجود.',
        ];
    }
}
