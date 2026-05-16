<?php

declare(strict_types=1);

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class StorePayrollRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'month.required' => 'الشهر مطلوب.',
            'month.min' => 'الشهر يجب أن يكون بين 1 و 12.',
            'year.required' => 'السنة مطلوبة.',
        ];
    }
}
