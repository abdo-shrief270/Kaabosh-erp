<?php

declare(strict_types=1);

namespace App\Http\Requests\AccountsPayable;

use Illuminate\Foundation\Http\FormRequest;

class StoreBillPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'payment_method' => ['required', 'string', 'in:cash,bank_transfer,check,mobile_wallet,other'],
            'payment_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'check_number' => ['nullable', 'string', 'max:30', 'required_if:payment_method,check'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'amount.required' => 'مبلغ الدفع مطلوب.',
            'amount.min' => 'مبلغ الدفع يجب أن يكون أكبر من صفر.',
            'payment_method.required' => 'طريقة الدفع مطلوبة.',
            'payment_method.in' => 'طريقة الدفع غير صالحة.',
            'payment_date.required' => 'تاريخ الدفع مطلوب.',
            'check_number.required_if' => 'رقم الشيك مطلوب عند اختيار الدفع بشيك.',
        ];
    }
}
