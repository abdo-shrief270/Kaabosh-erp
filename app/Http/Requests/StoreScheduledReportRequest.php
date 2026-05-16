<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScheduledReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'report_type' => ['required', 'string', 'max:50', Rule::in([
                'trial_balance', 'income_statement', 'balance_sheet',
                'cash_flow', 'aging_report', 'vat_return', 'custom',
            ])],
            'report_config' => ['required', 'array'],
            'report_config.from' => ['nullable', 'date'],
            'report_config.to' => ['nullable', 'date'],
            'report_config.as_of' => ['nullable', 'date'],
            'report_config.currency' => ['nullable', 'string', 'max:3'],
            'schedule_type' => ['required', 'string', 'max:20', Rule::in([
                'daily', 'weekly', 'monthly', 'quarterly',
            ])],
            'schedule_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'schedule_time' => ['nullable', 'date_format:H:i'],
            'format' => ['nullable', 'string', 'max:20', Rule::in(['pdf', 'excel', 'csv'])],
            'recipients' => ['required', 'array', 'min:1', 'max:20'],
            'recipients.*' => ['required', 'email', 'max:255'],
            'subject_template' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
