<?php

declare(strict_types=1);

namespace App\Http\Requests\Banking;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $tenantId = app('tenant.id');
        $bankAccountId = $this->route('bank_account')?->id;

        return [
            'account_name' => ['sometimes', 'required', 'string', 'min:2', 'max:200'],
            'bank_name' => ['sometimes', 'required', 'string', 'min:2', 'max:200'],
            'branch' => ['sometimes', 'nullable', 'string', 'max:200'],
            'account_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            'iban' => [
                'sometimes',
                'nullable',
                'string',
                'max:34',
                Rule::unique('bank_accounts', 'iban')
                    ->where('tenant_id', $tenantId)
                    ->ignore($bankAccountId)
                    ->whereNull('deleted_at'),
            ],
            'swift_code' => ['sometimes', 'nullable', 'string', 'max:11'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'gl_account_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')->where('tenant_id', $tenantId),
            ],
            'opening_balance' => ['sometimes', 'nullable', 'numeric', 'min:-9999999999.99', 'max:9999999999.99'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
