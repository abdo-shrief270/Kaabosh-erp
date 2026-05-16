<?php

declare(strict_types=1);

namespace App\Http\Requests\VendorProduct;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVendorProductRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:200'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'unit_price' => ['sometimes', 'required', 'numeric', 'min:0', 'max:9999999999.99'],
            'default_vat_rate' => ['sometimes', 'nullable', 'numeric', 'between:0,100'],
            'default_account_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')->where('tenant_id', $tenantId),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
