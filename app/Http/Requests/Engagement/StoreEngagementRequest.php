<?php

declare(strict_types=1);

namespace App\Http\Requests\Engagement;

use App\Domain\Engagement\Enums\EngagementStatus;
use App\Domain\Engagement\Enums\EngagementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEngagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')->where('tenant_id', app('tenant.id'))],
            'fiscal_year_id' => ['nullable', 'integer', Rule::exists('fiscal_years', 'id')->where('tenant_id', app('tenant.id'))],
            'engagement_type' => ['required', Rule::enum(EngagementType::class)],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::enum(EngagementStatus::class)],
            'manager_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'partner_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'planned_hours' => ['sometimes', 'numeric', 'min:0', 'max:999.99'],
            'budget_amount' => ['sometimes', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'deadline' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'client_id.required' => 'العميل مطلوب.',
            'engagement_type.required' => 'نوع المهمة مطلوب.',
            'name_ar.required' => 'الاسم بالعربية مطلوب.',
        ];
    }
}
