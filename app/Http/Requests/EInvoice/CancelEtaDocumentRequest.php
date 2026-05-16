<?php

declare(strict_types=1);

namespace App\Http\Requests\EInvoice;

use Illuminate\Foundation\Http\FormRequest;

class CancelEtaDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'reason.required' => 'سبب الإلغاء مطلوب.',
            'reason.max' => 'سبب الإلغاء يجب ألا يتجاوز 500 حرف.',
        ];
    }
}
