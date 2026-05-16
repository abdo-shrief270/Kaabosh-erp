<?php

declare(strict_types=1);

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

class SetupCoaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'template' => ['required', 'string', 'in:general,trading,services'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'template.required' => 'قالب دليل الحسابات مطلوب.',
            'template.in' => 'القالب يجب أن يكون أحد: عام، تجاري، خدمات.',
        ];
    }
}
