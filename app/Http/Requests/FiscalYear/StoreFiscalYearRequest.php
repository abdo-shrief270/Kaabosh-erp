<?php

declare(strict_types=1);

namespace App\Http\Requests\FiscalYear;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFiscalYearRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('fiscal_years')->where('tenant_id', $tenantId),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'اسم السنة المالية مطلوب.',
            'name.unique' => 'اسم السنة المالية مسجل بالفعل.',
            'start_date.required' => 'تاريخ بداية السنة المالية مطلوب.',
            'end_date.required' => 'تاريخ نهاية السنة المالية مطلوب.',
            'end_date.after' => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية.',
        ];
    }
}
