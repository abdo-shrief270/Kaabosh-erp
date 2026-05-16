<?php

declare(strict_types=1);

namespace App\Http\Requests\StatementTemplate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStatementTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['income_statement', 'balance_sheet', 'cash_flow'])],
            'structure' => ['required', 'array'],
            'structure.sections' => ['required', 'array', 'min:1'],
            'structure.sections.*.id' => ['required', 'string', 'max:50'],
            'structure.sections.*.label_ar' => ['required', 'string', 'max:255'],
            'structure.sections.*.label_en' => ['nullable', 'string', 'max:255'],
            'structure.sections.*.accounts' => ['nullable', 'array'],
            'structure.sections.*.accounts.type' => ['nullable', 'string', Rule::in(['asset', 'liability', 'equity', 'revenue', 'expense'])],
            'structure.sections.*.accounts.codes_from' => ['nullable', 'string', 'max:20'],
            'structure.sections.*.accounts.codes_to' => ['nullable', 'string', 'max:20'],
            'structure.sections.*.accounts.ids' => ['nullable', 'array'],
            'structure.sections.*.accounts.ids.*' => ['integer'],
            'structure.sections.*.subtotal' => ['nullable', 'boolean'],
            'structure.sections.*.negate' => ['nullable', 'boolean'],
            'structure.sections.*.is_calculated' => ['nullable', 'boolean'],
            'structure.sections.*.formula' => ['nullable', 'string', 'max:500'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name_ar.required' => 'اسم القالب بالعربية مطلوب.',
            'type.required' => 'نوع القائمة المالية مطلوب.',
            'type.in' => 'نوع القائمة المالية غير صالح.',
            'structure.required' => 'هيكل القالب مطلوب.',
            'structure.sections.required' => 'يجب أن يحتوي القالب على قسم واحد على الأقل.',
            'structure.sections.*.id.required' => 'معرّف القسم مطلوب.',
            'structure.sections.*.label_ar.required' => 'اسم القسم بالعربية مطلوب.',
        ];
    }
}
