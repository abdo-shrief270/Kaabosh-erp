<?php

declare(strict_types=1);

namespace App\Http\Requests\VendorProduct;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVendorProductRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:2', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'default_vat_rate' => ['nullable', 'numeric', 'between:0,100'],
            // Account must belong to the same tenant. Accepting null lets a
            // vendor product be saved without a default account (the bill
            // line picker will then leave the account blank for the user).
            'default_account_id' => [
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')->where('tenant_id', $tenantId),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
