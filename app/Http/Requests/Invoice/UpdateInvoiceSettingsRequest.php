<?php

declare(strict_types=1);

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvoiceSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'invoice_prefix' => ['nullable', 'string', 'max:10'],
            'credit_note_prefix' => ['nullable', 'string', 'max:10'],
            'debit_note_prefix' => ['nullable', 'string', 'max:10'],
            'default_due_days' => ['nullable', 'integer', 'min:1'],
            'default_vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'default_payment_terms' => ['nullable', 'string', 'max:2000'],
            'default_notes' => ['nullable', 'string', 'max:2000'],
            'ar_account_id' => ['nullable', 'integer', Rule::exists('accounts', 'id')->where('tenant_id', app('tenant.id'))],
            'revenue_account_id' => ['nullable', 'integer', Rule::exists('accounts', 'id')->where('tenant_id', app('tenant.id'))],
            'vat_account_id' => ['nullable', 'integer', Rule::exists('accounts', 'id')->where('tenant_id', app('tenant.id'))],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'invoice_prefix.max' => 'بادئة الفاتورة يجب ألا تتجاوز 10 أحرف.',
            'credit_note_prefix.max' => 'بادئة إشعار الدائن يجب ألا تتجاوز 10 أحرف.',
            'debit_note_prefix.max' => 'بادئة إشعار المدين يجب ألا تتجاوز 10 أحرف.',
            'default_due_days.integer' => 'أيام الاستحقاق يجب أن تكون عدداً صحيحاً.',
            'default_due_days.min' => 'أيام الاستحقاق يجب أن تكون يوماً واحداً على الأقل.',
            'default_vat_rate.numeric' => 'نسبة الضريبة يجب أن تكون رقماً.',
            'default_vat_rate.min' => 'نسبة الضريبة يجب أن تكون صفراً أو أكثر.',
            'default_vat_rate.max' => 'نسبة الضريبة يجب ألا تتجاوز 100%.',
            'default_payment_terms.max' => 'شروط الدفع يجب ألا تتجاوز 2000 حرف.',
            'default_notes.max' => 'الملاحظات يجب ألا تتجاوز 2000 حرف.',
            'ar_account_id.exists' => 'حساب المدينين المحدد غير موجود.',
            'revenue_account_id.exists' => 'حساب الإيرادات المحدد غير موجود.',
            'vat_account_id.exists' => 'حساب الضريبة المحدد غير موجود.',
        ];
    }
}
