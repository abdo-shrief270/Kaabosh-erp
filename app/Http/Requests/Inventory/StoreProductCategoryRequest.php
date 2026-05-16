<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductCategoryRequest extends FormRequest
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
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:20', Rule::unique('product_categories')->where('tenant_id', $tenantId)],
            'parent_id' => ['nullable', 'integer', Rule::exists('product_categories', 'id')->where('tenant_id', $tenantId)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name_ar.required' => 'اسم الفئة بالعربية مطلوب.',
            'code.unique' => 'كود الفئة مستخدم بالفعل.',
            'code.max' => 'كود الفئة يجب ألا يتجاوز 20 حرف.',
            'parent_id.exists' => 'الفئة الأم المحددة غير موجودة.',
        ];
    }
}
