<?php

declare(strict_types=1);

namespace App\Http\Requests\Workflow;

use App\Domain\Workflow\Enums\ApproverType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApprovalWorkflowRequest extends FormRequest
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
            'entity_type' => ['required', 'string', 'max:50', Rule::in(['bill', 'expense', 'journal_entry', 'leave_request', 'payroll_run'])],
            'is_active' => ['nullable', 'boolean'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.step_order' => ['nullable', 'integer', 'min:1'],
            'steps.*.approver_type' => ['required', 'string', Rule::in(array_column(ApproverType::cases(), 'value'))],
            'steps.*.approver_id' => ['nullable', 'integer'],
            'steps.*.approval_limit' => ['nullable', 'numeric', 'min:0'],
            'steps.*.timeout_hours' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name_ar.required' => 'اسم سير العمل بالعربية مطلوب.',
            'entity_type.required' => 'نوع الكيان مطلوب.',
            'entity_type.in' => 'نوع الكيان غير صالح.',
            'steps.required' => 'يجب إضافة خطوة واحدة على الأقل.',
            'steps.min' => 'يجب إضافة خطوة واحدة على الأقل.',
            'steps.*.approver_type.required' => 'نوع المعتمد مطلوب لكل خطوة.',
            'steps.*.approver_type.in' => 'نوع المعتمد غير صالح.',
        ];
    }
}
