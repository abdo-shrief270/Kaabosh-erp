<?php

declare(strict_types=1);

namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseAddOnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'add_on_id' => ['required', 'integer', Rule::exists('add_ons', 'id')->where('is_active', true)],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:100'],
            'billing_cycle' => ['nullable', 'string', Rule::in(['monthly', 'annual', 'once'])],
            'payment_method' => ['nullable', 'string', Rule::in(['paymob', 'fawry', 'bank_transfer'])],
            'gateway_payment_id' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'add_on_id.required' => 'الإضافة مطلوبة.',
            'add_on_id.exists' => 'الإضافة غير متاحة.',
            'quantity.min' => 'الكمية يجب أن تكون 1 على الأقل.',
            'quantity.max' => 'الحد الأقصى للكمية 100.',
        ];
    }
}
