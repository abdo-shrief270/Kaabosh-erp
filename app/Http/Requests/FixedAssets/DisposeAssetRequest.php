<?php

declare(strict_types=1);

namespace App\Http\Requests\FixedAssets;

use Illuminate\Foundation\Http\FormRequest;

class DisposeAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'disposal_date' => ['required', 'date'],
            'disposal_type' => ['required', 'string', 'in:sale,scrap,donation,write_off'],
            'proceeds' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing([
            'proceeds' => 0,
        ]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'disposal_date.required' => 'تاريخ الاستبعاد مطلوب.',
            'disposal_type.required' => 'نوع الاستبعاد مطلوب.',
            'disposal_type.in' => 'نوع الاستبعاد غير صالح.',
            'proceeds.numeric' => 'قيمة العائد يجب أن تكون رقمًا.',
            'proceeds.min' => 'قيمة العائد لا يمكن أن تكون سالبة.',
        ];
    }
}
