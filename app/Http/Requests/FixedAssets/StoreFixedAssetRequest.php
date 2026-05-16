<?php

declare(strict_types=1);

namespace App\Http\Requests\FixedAssets;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFixedAssetRequest extends FormRequest
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
            'category_id' => [
                'required',
                'integer',
                Rule::exists('asset_categories', 'id')->where('tenant_id', $tenantId),
            ],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('fixed_assets')->where('tenant_id', $tenantId),
            ],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'acquisition_date' => ['required', 'date'],
            'acquisition_cost' => ['required', 'numeric', 'min:0.01'],
            'depreciation_method' => ['sometimes', 'string', 'in:straight_line,declining_balance,units_of_production'],
            'useful_life_years' => ['required', 'numeric', 'min:0.5'],
            'salvage_value' => ['nullable', 'numeric', 'min:0'],
            'depreciation_start_date' => ['required', 'date', 'after_or_equal:acquisition_date'],
            'currency' => ['nullable', 'string', 'size:3'],
            'vendor_id' => [
                'nullable',
                'integer',
                Rule::exists('vendors', 'id')->where('tenant_id', $tenantId),
            ],
            'purchase_invoice_ref' => ['nullable', 'string', 'max:100'],
            'responsible_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'category_id.required' => 'فئة الأصل مطلوبة.',
            'category_id.exists' => 'فئة الأصل غير موجودة.',
            'code.required' => 'كود الأصل مطلوب.',
            'code.unique' => 'كود الأصل مسجل بالفعل.',
            'name_ar.required' => 'اسم الأصل بالعربية مطلوب.',
            'acquisition_date.required' => 'تاريخ الاقتناء مطلوب.',
            'acquisition_cost.required' => 'تكلفة الاقتناء مطلوبة.',
            'acquisition_cost.min' => 'تكلفة الاقتناء يجب أن تكون أكبر من صفر.',
            'useful_life_years.required' => 'العمر الافتراضي مطلوب.',
            'useful_life_years.min' => 'العمر الافتراضي يجب أن يكون 0.5 سنة على الأقل.',
            'depreciation_start_date.required' => 'تاريخ بدء الإهلاك مطلوب.',
            'depreciation_start_date.after_or_equal' => 'تاريخ بدء الإهلاك يجب أن يكون بعد أو يساوي تاريخ الاقتناء.',
            'currency.size' => 'رمز العملة يجب أن يكون 3 أحرف.',
            'vendor_id.exists' => 'المورد غير موجود.',
            'responsible_user_id.exists' => 'المستخدم المسؤول غير موجود.',
            'depreciation_method.in' => 'طريقة الإهلاك غير صالحة.',
        ];
    }
}
