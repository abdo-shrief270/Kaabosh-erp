<?php

declare(strict_types=1);

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'entity_type' => ['required', 'string', 'max:50', Rule::in(['bill', 'expense', 'journal_entry', 'leave_request', 'payroll_run'])],
            'entity_id' => ['required', 'integer', 'min:1'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'entity_type.required' => 'نوع الكيان مطلوب.',
            'entity_type.in' => 'نوع الكيان غير صالح.',
            'entity_id.required' => 'معرّف الكيان مطلوب.',
        ];
    }
}
