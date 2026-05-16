<?php

declare(strict_types=1);

namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /**
     * Normalize the payload before validation so the SPA's preferred field
     * names (`new_plan_id`, `effective_date`) and the legacy contract
     * (`plan_id`, `billing_cycle`) both validate cleanly.
     */
    protected function prepareForValidation(): void
    {
        // The SPA calls this field `new_plan_id`; older clients may still
        // pass `plan_id`. Merge them so a single rule covers both.
        if (! $this->filled('plan_id') && $this->filled('new_plan_id')) {
            $this->merge(['plan_id' => $this->input('new_plan_id')]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer', Rule::exists('plans', 'id')],
            'new_plan_id' => ['sometimes', 'integer', Rule::exists('plans', 'id')],
            'billing_cycle' => ['nullable', 'string', Rule::in(['monthly', 'annual'])],
            // SPA sends this to express upgrade-vs-downgrade timing. Service
            // currently auto-detects from price, but accepting the field
            // means the validator stops rejecting valid requests.
            'effective_date' => ['nullable', 'string', Rule::in(['immediate', 'end_of_period'])],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'plan_id.required' => 'يجب اختيار الخطة الجديدة.',
            'plan_id.exists' => 'الخطة المحددة غير موجودة.',
            'billing_cycle.in' => 'دورة الفوترة يجب أن تكون شهرية أو سنوية.',
            'effective_date.in' => 'تاريخ التفعيل يجب أن يكون "فوري" أو "نهاية الدورة".',
        ];
    }
}
