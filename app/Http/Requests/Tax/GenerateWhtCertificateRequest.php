<?php

declare(strict_types=1);

namespace App\Http\Requests\Tax;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateWhtCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $tenantId = app('tenant.id');

        return [
            'vendor_id' => [
                'required',
                'integer',
                Rule::exists('vendors', 'id')->where('tenant_id', $tenantId),
            ],
            'period_from' => ['required', 'date'],
            'period_to' => ['required', 'date', 'after_or_equal:period_from'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'vendor_id.required' => 'المورد مطلوب.',
            'vendor_id.exists' => 'المورد غير موجود.',
            'period_from.required' => 'تاريخ بداية الفترة مطلوب.',
            'period_to.required' => 'تاريخ نهاية الفترة مطلوب.',
            'period_to.after_or_equal' => 'تاريخ نهاية الفترة يجب أن يكون بعد أو يساوي تاريخ البداية.',
        ];
    }
}
