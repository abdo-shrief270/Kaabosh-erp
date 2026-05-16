<?php

declare(strict_types=1);

namespace App\Http\Requests\Tax;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaxAdjustmentRequest extends FormRequest
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
            'fiscal_year_id' => [
                'required',
                'integer',
                Rule::exists('fiscal_years', 'id')->where('tenant_id', $tenantId),
            ],
            'adjustment_type' => [
                'required',
                'string',
                'in:non_deductible_expense,tax_depreciation_diff,tax_loss_carryforward,exempt_income,other',
            ],
            'description_ar' => ['required', 'string', 'max:500'],
            'description_en' => ['nullable', 'string', 'max:500'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'is_addition' => ['required', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'fiscal_year_id.required' => 'السنة المالية مطلوبة.',
            'fiscal_year_id.exists' => 'السنة المالية غير موجودة.',
            'adjustment_type.required' => 'نوع التسوية مطلوب.',
            'adjustment_type.in' => 'نوع التسوية غير صالح.',
            'description_ar.required' => 'الوصف بالعربية مطلوب.',
            'amount.required' => 'المبلغ مطلوب.',
            'amount.min' => 'المبلغ يجب أن يكون أكبر من صفر.',
            'is_addition.required' => 'يجب تحديد ما إذا كانت التسوية إضافة أو خصم.',
        ];
    }
}
