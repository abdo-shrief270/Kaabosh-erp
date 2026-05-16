<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() === true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'plan_id' => ['sometimes', 'integer', 'exists:plans,id'],
            'status' => ['sometimes', 'string', 'in:trial,active,past_due,cancelled,expired'],
            'price' => ['sometimes', 'numeric', 'min:0', 'max:9999999999.99'],
            'current_period_end' => ['nullable', 'date'],
            'trial_ends_at' => ['nullable', 'date'],
        ];
    }
}
