<?php

declare(strict_types=1);

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'hire_date' => ['sometimes', 'date'],
            'department' => ['nullable', 'string', 'max:100'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'base_salary' => ['sometimes', 'numeric', 'min:0', 'max:9999999999.99'],
            'social_insurance_number' => ['nullable', 'string', 'max:50'],
            'bank_account' => ['nullable', 'string', 'max:100'],
            'is_insured' => ['sometimes', 'boolean'],
        ];
    }
}
