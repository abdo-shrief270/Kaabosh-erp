<?php

declare(strict_types=1);

namespace App\Http\Requests\Expenses;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
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
            'expense_category_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('expense_categories', 'id')->where('tenant_id', $tenantId),
            ],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'currency' => ['nullable', 'string', 'size:3'],
            'date' => ['sometimes', 'required', 'date'],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'vendor_id' => [
                'nullable',
                'integer',
                Rule::exists('vendors', 'id')->where('tenant_id', $tenantId),
            ],
            'description' => ['sometimes', 'required', 'string', 'max:1000'],
            'receipt' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,application/pdf', 'max:10240'],
            'cost_center' => ['nullable', 'string', 'max:50'],
            'payment_method' => ['nullable', 'string', Rule::in(['cash', 'bank_transfer', 'company_card', 'personal'])],
            'vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'expense_category_id.required' => 'فئة المصروف مطلوبة.',
            'expense_category_id.exists' => 'فئة المصروف غير موجودة.',
            'amount.required' => 'مبلغ المصروف مطلوب.',
            'amount.min' => 'مبلغ المصروف يجب أن يكون أكبر من صفر.',
            'date.required' => 'تاريخ المصروف مطلوب.',
            'description.required' => 'وصف المصروف مطلوب.',
            'description.max' => 'وصف المصروف يجب ألا يتجاوز 1000 حرف.',
            'receipt.mimetypes' => 'الإيصال يجب أن يكون صورة (JPEG أو PNG) أو ملف PDF.',
            'receipt.max' => 'حجم الإيصال يجب ألا يتجاوز 10 ميجابايت.',
            'currency.size' => 'رمز العملة يجب أن يكون 3 أحرف.',
            'payment_method.in' => 'طريقة الدفع غير صالحة.',
            'vat_rate.min' => 'نسبة ضريبة القيمة المضافة لا يمكن أن تكون سالبة.',
            'vat_rate.max' => 'نسبة ضريبة القيمة المضافة لا يمكن أن تتجاوز 100%.',
            'vendor_id.exists' => 'المورد غير موجود.',
        ];
    }
}
