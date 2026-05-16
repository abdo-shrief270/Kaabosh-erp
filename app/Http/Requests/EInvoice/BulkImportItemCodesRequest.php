<?php

declare(strict_types=1);

namespace App\Http\Requests\EInvoice;

use Illuminate\Foundation\Http\FormRequest;

class BulkImportItemCodesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'codes' => ['required', 'array', 'min:1', 'max:1000'],
            'codes.*.code' => ['required', 'string', 'max:50'],
            'codes.*.code_type' => ['required', 'string', 'in:GS1,EGS'],
            'codes.*.description_ar' => ['required', 'string', 'max:500'],
            'codes.*.description_en' => ['nullable', 'string', 'max:500'],
            'codes.*.category' => ['nullable', 'string', 'max:50'],
        ];
    }
}
