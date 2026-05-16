<?php

declare(strict_types=1);

namespace App\Http\Requests\AccountsPayable;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBillRequest extends FormRequest
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
            'vendor_id' => [
                'required',
                'integer',
                Rule::exists('vendors', 'id')->where('tenant_id', $tenantId),
            ],
            'vendor_invoice_number' => ['nullable', 'string', 'max:50'],
            'type' => ['sometimes', 'string', 'in:bill,debit_note,credit_note'],
            'date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:date'],
            'currency' => ['nullable', 'string', 'size:3'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.000001'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'payment_terms' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.account_id' => [
                'required',
                'integer',
                Rule::exists('accounts', 'id')->where('tenant_id', $tenantId),
            ],
            // Optional FK back to the saved vendor product. Tenant-scoped to
            // prevent picking another tenant's product on a bill line.
            'lines.*.vendor_product_id' => [
                'nullable',
                'integer',
                Rule::exists('vendor_products', 'id')->where('tenant_id', $tenantId),
            ],
            'lines.*.description' => ['nullable', 'string', 'max:500'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'lines.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.wht_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.cost_center' => ['nullable', 'string', 'max:50'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'vendor_id.required' => 'المورد مطلوب.',
            'vendor_id.exists' => 'المورد غير موجود.',
            'date.required' => 'تاريخ الفاتورة مطلوب.',
            'due_date.required' => 'تاريخ الاستحقاق مطلوب.',
            'due_date.after_or_equal' => 'تاريخ الاستحقاق يجب أن يكون بعد أو يساوي تاريخ الفاتورة.',
            'lines.required' => 'يجب إضافة بند واحد على الأقل.',
            'lines.min' => 'يجب إضافة بند واحد على الأقل.',
            'lines.*.account_id.required' => 'الحساب مطلوب لكل بند.',
            'lines.*.account_id.exists' => 'الحساب غير موجود.',
            'lines.*.quantity.required' => 'الكمية مطلوبة لكل بند.',
            'lines.*.unit_price.required' => 'سعر الوحدة مطلوب لكل بند.',
            'type.in' => 'نوع المستند غير صالح.',
        ];
    }
}
