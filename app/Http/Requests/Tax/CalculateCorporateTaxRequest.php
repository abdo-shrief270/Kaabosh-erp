<?php

declare(strict_types=1);

namespace App\Http\Requests\Tax;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CalculateCorporateTaxRequest extends FormRequest
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
            'fiscal_year_id' => [
                'required',
                'integer',
                Rule::exists('fiscal_years', 'id')->where('tenant_id', $tenantId),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'fiscal_year_id.required' => 'السنة المالية مطلوبة.',
            'fiscal_year_id.exists' => 'السنة المالية غير موجودة.',
        ];
    }
}
