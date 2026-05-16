<?php

declare(strict_types=1);

namespace App\Http\Requests\Subscription;

use App\Domain\Subscription\Enums\PaymentGateway;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscribeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer', Rule::exists('plans', 'id')],
            'billing_cycle' => ['required', 'string', Rule::in(['monthly', 'annual'])],
            'gateway' => ['nullable', 'string', Rule::in(array_column(PaymentGateway::cases(), 'value'))],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'plan_id.required' => 'يجب اختيار خطة الاشتراك.',
            'plan_id.exists' => 'الخطة المحددة غير موجودة.',
            'billing_cycle.required' => 'دورة الفوترة مطلوبة.',
            'billing_cycle.in' => 'دورة الفوترة يجب أن تكون شهرية أو سنوية.',
            'gateway.in' => 'بوابة الدفع غير صالحة.',
        ];
    }
}
