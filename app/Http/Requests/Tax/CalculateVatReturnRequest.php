<?php

declare(strict_types=1);

namespace App\Http\Requests\Tax;

use Illuminate\Foundation\Http\FormRequest;

class CalculateVatReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'period_from' => ['required', 'date'],
            'period_to' => ['required', 'date', 'after_or_equal:period_from'],
            'return_type' => ['required', 'string', 'in:vat_monthly,vat_quarterly'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'period_from.required' => 'تاريخ بداية الفترة مطلوب.',
            'period_to.required' => 'تاريخ نهاية الفترة مطلوب.',
            'period_to.after_or_equal' => 'تاريخ نهاية الفترة يجب أن يكون بعد أو يساوي تاريخ البداية.',
            'return_type.required' => 'نوع الإقرار مطلوب.',
            'return_type.in' => 'نوع الإقرار غير صالح.',
        ];
    }
}
