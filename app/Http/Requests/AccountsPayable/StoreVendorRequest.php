<?php

declare(strict_types=1);

namespace App\Http\Requests\AccountsPayable;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVendorRequest extends FormRequest
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
            // Either AR or EN name must be present — the SPA enforces this in the
            // form schema and surfaces a single combined error.
            'name_ar' => ['required_without:name_en', 'nullable', 'string', 'max:255'],
            'name_en' => ['required_without:name_ar', 'nullable', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('vendors')->where('tenant_id', $tenantId),
            ],
            'tax_id' => ['nullable', 'string', 'max:20'],
            'commercial_register' => ['nullable', 'string', 'max:30'],
            'vat_registration' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address_ar' => ['nullable', 'string', 'max:1000'],
            'address_en' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'size:2'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account' => ['nullable', 'string', 'max:255'],
            'iban' => ['nullable', 'string', 'max:34'],
            'swift_code' => ['nullable', 'string', 'max:11'],
            'payment_terms' => ['nullable', 'string', 'in:net_15,net_30,net_45,net_60,net_90,due_on_receipt,cod,prepaid'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'contacts' => ['nullable', 'array'],
            'contacts.*.name' => ['required_with:contacts', 'string', 'max:255'],
            'contacts.*.role' => ['nullable', 'string', 'max:120'],
            'contacts.*.email' => ['nullable', 'email'],
            'contacts.*.phone' => ['nullable', 'string', 'max:30'],
            'contacts.*.is_primary' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name_ar.required_without' => 'اسم المورد بالعربية أو بالإنجليزية مطلوب.',
            'name_en.required_without' => 'Vendor name (Arabic or English) is required.',
            'code.unique' => 'كود المورد مسجل بالفعل.',
            'email.email' => 'البريد الإلكتروني غير صالح.',
            'country.size' => 'رمز الدولة يجب أن يكون حرفين.',
            'currency.size' => 'رمز العملة يجب أن يكون 3 أحرف.',
            'payment_terms.in' => 'شروط الدفع غير صالحة.',
        ];
    }
}
