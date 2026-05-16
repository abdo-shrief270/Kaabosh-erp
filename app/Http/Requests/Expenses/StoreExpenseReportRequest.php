<?php

declare(strict_types=1);

namespace App\Http\Requests\Expenses;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseReportRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'period_from' => ['required', 'date'],
            'period_to' => ['required', 'date', 'after_or_equal:period_from'],
            'expense_ids' => ['nullable', 'array'],
            'expense_ids.*' => [
                'integer',
                Rule::exists('expenses', 'id')->where('tenant_id', $tenantId),
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'title.required' => 'عنوان التقرير مطلوب.',
            'period_from.required' => 'تاريخ بداية الفترة مطلوب.',
            'period_to.required' => 'تاريخ نهاية الفترة مطلوب.',
            'period_to.after_or_equal' => 'تاريخ نهاية الفترة يجب أن يكون بعد أو يساوي تاريخ البداية.',
            'expense_ids.array' => 'قائمة المصروفات يجب أن تكون مصفوفة.',
            'expense_ids.*.exists' => 'أحد المصروفات المحددة غير موجود.',
        ];
    }
}
