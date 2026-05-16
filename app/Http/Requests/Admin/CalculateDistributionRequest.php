<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CalculateDistributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() === true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'expenses' => ['sometimes', 'array'],
            'expenses.*.tenant_id' => ['required_with:expenses', 'integer', 'exists:tenants,id'],
            'expenses.*.amount' => ['required_with:expenses', 'numeric', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'month.required' => 'الشهر مطلوب.',
            'year.required' => 'السنة مطلوبة.',
        ];
    }
}
