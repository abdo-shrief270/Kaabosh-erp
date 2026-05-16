<?php

declare(strict_types=1);

namespace App\Http\Requests\Collection;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LogCollectionActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'invoice_id' => ['required', 'integer', Rule::exists('invoices', 'id')->where('tenant_id', app('tenant.id'))],
            'action_type' => ['required', 'string', Rule::in(['call', 'email', 'sms', 'whatsapp', 'meeting', 'legal_notice', 'write_off', 'payment_commitment'])],
            'action_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'outcome' => ['nullable', 'string', Rule::in(['no_answer', 'promised_payment', 'disputed', 'partial_payment', 'paid', 'escalated'])],
            'commitment_date' => ['nullable', 'date', 'after_or_equal:today', 'required_if:outcome,promised_payment'],
            'commitment_amount' => ['nullable', 'numeric', 'min:0.01', 'required_if:outcome,promised_payment'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'invoice_id.required' => 'الفاتورة مطلوبة.',
            'invoice_id.exists' => 'الفاتورة المحددة غير موجودة.',
            'action_type.required' => 'نوع الإجراء مطلوب.',
            'action_type.in' => 'نوع الإجراء غير صالح.',
            'action_date.required' => 'تاريخ الإجراء مطلوب.',
            'action_date.date' => 'تاريخ الإجراء غير صالح.',
            'notes.max' => 'الملاحظات يجب ألا تتجاوز 2000 حرف.',
            'outcome.in' => 'نتيجة الإجراء غير صالحة.',
            'commitment_date.date' => 'تاريخ الالتزام غير صالح.',
            'commitment_date.after_or_equal' => 'تاريخ الالتزام يجب أن يكون اليوم أو بعده.',
            'commitment_date.required_if' => 'تاريخ الالتزام مطلوب عند وعد بالسداد.',
            'commitment_amount.numeric' => 'مبلغ الالتزام يجب أن يكون رقماً.',
            'commitment_amount.min' => 'مبلغ الالتزام يجب أن يكون أكبر من صفر.',
            'commitment_amount.required_if' => 'مبلغ الالتزام مطلوب عند وعد بالسداد.',
        ];
    }
}
