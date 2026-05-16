<?php

declare(strict_types=1);

namespace App\Http\Requests\EInvoice;

use Illuminate\Foundation\Http\FormRequest;

class BulkAssignItemCodesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'mappings' => ['required', 'array', 'min:1', 'max:500'],
            'mappings.*.eta_item_code_id' => ['required', 'integer', 'exists:eta_item_codes,id'],
            'mappings.*.product_id' => ['nullable', 'integer'],
            'mappings.*.description_pattern' => ['nullable', 'string', 'max:255'],
            'mappings.*.priority' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
