<?php

declare(strict_types=1);

namespace App\Http\Requests\Banking;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $tenantId = app('tenant.id');

        return [
            'account_name' => ['required', 'string', 'min:2', 'max:200'],
            'bank_name' => ['required', 'string', 'min:2', 'max:200'],
            'branch' => ['nullable', 'string', 'max:200'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'iban' => [
                'nullable',
                'string',
                'max:34',
                Rule::unique('bank_accounts', 'iban')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at'),
            ],
            'swift_code' => ['nullable', 'string', 'max:11'],
            'currency' => ['nullable', 'string', 'size:3'],
            'gl_account_id' => [
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')->where('tenant_id', $tenantId),
            ],
            'opening_balance' => ['nullable', 'numeric', 'min:-9999999999.99', 'max:9999999999.99'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'account_name.required' => 'اسم الحساب مطلوب.',
            'bank_name.required' => 'اسم البنك مطلوب.',
            'iban.unique' => 'رقم IBAN مسجَّل بالفعل لحساب آخر.',
            'gl_account_id.exists' => 'الحساب المحاسبي غير موجود.',
            'currency.size' => 'رمز العملة يجب أن يكون 3 أحرف.',
        ];
    }
}
