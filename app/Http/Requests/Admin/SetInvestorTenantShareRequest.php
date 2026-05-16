<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SetInvestorTenantShareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() === true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'ownership_percentage' => ['required', 'numeric', 'min:0.01', 'max:100'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'tenant_id.required' => 'الحساب مطلوب.',
            'ownership_percentage.required' => 'نسبة الملكية مطلوبة.',
            'ownership_percentage.max' => 'نسبة الملكية لا يمكن أن تتجاوز 100%.',
        ];
    }
}
