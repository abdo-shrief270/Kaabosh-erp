<?php

declare(strict_types=1);

namespace App\Http\Requests\ClientPortal;

use Illuminate\Foundation\Http\FormRequest;

class FirmSendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'subject.required' => 'عنوان الرسالة مطلوب.',
            'body.required' => 'نص الرسالة مطلوب.',
        ];
    }
}
