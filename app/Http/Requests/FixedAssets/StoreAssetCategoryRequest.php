<?php

declare(strict_types=1);

namespace App\Http\Requests\FixedAssets;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetCategoryRequest extends FormRequest
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
            'code' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('asset_categories')->where('tenant_id', $tenantId),
            ],
            'depreciation_method' => ['required', 'string', 'in:straight_line,declining_balance,units_of_production'],
            'default_useful_life_years' => ['required', 'numeric', 'min:0.5'],
            'default_salvage_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'asset_account_id' => [
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')->where('tenant_id', $tenantId),
            ],
            'depreciation_expense_account_id' => [
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')->where('tenant_id', $tenantId),
            ],
            'accumulated_depreciation_account_id' => [
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')->where('tenant_id', $tenantId),
            ],
            'disposal_account_id' => [
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
            'name_ar.required' => 'اسم الفئة بالعربية مطلوب.',
            'code.unique' => 'كود الفئة مسجل بالفعل.',
            'depreciation_method.required' => 'طريقة الإهلاك مطلوبة.',
            'depreciation_method.in' => 'طريقة الإهلاك غير صالحة.',
            'default_useful_life_years.required' => 'العمر الافتراضي مطلوب.',
            'default_useful_life_years.min' => 'العمر الافتراضي يجب أن يكون 0.5 سنة على الأقل.',
            'default_salvage_percent.min' => 'نسبة القيمة التخريدية لا يمكن أن تكون سالبة.',
            'default_salvage_percent.max' => 'نسبة القيمة التخريدية لا يمكن أن تتجاوز 100%.',
            'asset_account_id.exists' => 'حساب الأصل غير موجود.',
            'depreciation_expense_account_id.exists' => 'حساب مصروف الإهلاك غير موجود.',
            'accumulated_depreciation_account_id.exists' => 'حساب مجمع الإهلاك غير موجود.',
            'disposal_account_id.exists' => 'حساب الاستبعاد غير موجود.',
        ];
    }
}
