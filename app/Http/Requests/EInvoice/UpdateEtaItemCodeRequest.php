<?php

declare(strict_types=1);

namespace App\Http\Requests\EInvoice;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEtaItemCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'code_type' => ['sometimes', 'string', 'in:GS1,EGS'],
            'item_code' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('eta_item_codes', 'item_code')
                    ->where('tenant_id', app('tenant.id'))
                    ->ignore($this->route('item_code')),
            ],
            'description' => ['sometimes', 'string', 'max:500'],
            'description_ar' => ['nullable', 'string', 'max:500'],
            'unit_type' => ['nullable', 'string', 'max:10'],
            'default_tax_type' => ['nullable', 'string', 'max:10'],
            'default_tax_subtype' => ['nullable', 'string', 'max:10'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'code_type.in' => 'نوع الكود يجب أن يكون GS1 أو EGS.',
            'item_code.unique' => 'كود الصنف مستخدم بالفعل.',
        ];
    }
}
