<?php

declare(strict_types=1);

namespace App\Http\Requests\Engagement;

use App\Domain\Engagement\Enums\EngagementStatus;
use App\Domain\Engagement\Enums\EngagementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEngagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'client_id' => ['sometimes', 'integer', Rule::exists('clients', 'id')->where('tenant_id', app('tenant.id'))],
            'fiscal_year_id' => ['nullable', 'integer', Rule::exists('fiscal_years', 'id')->where('tenant_id', app('tenant.id'))],
            'engagement_type' => ['sometimes', Rule::enum(EngagementType::class)],
            'name_ar' => ['sometimes', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::enum(EngagementStatus::class)],
            'manager_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'partner_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'planned_hours' => ['sometimes', 'numeric', 'min:0', 'max:999.99'],
            'actual_hours' => ['sometimes', 'numeric', 'min:0', 'max:999.99'],
            'budget_amount' => ['sometimes', 'numeric', 'min:0'],
            'actual_amount' => ['sometimes', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'deadline' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
