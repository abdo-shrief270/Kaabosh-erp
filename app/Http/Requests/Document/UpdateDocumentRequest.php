<?php

declare(strict_types=1);

namespace App\Http\Requests\Document;

use App\Domain\Document\Enums\DocumentCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'client_id' => ['nullable', 'integer', Rule::exists('clients', 'id')->where('tenant_id', app('tenant.id'))],
            'category' => ['nullable', 'string', Rule::in(array_column(DocumentCategory::cases(), 'value'))],
            'description' => ['nullable', 'string', 'max:1000'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.max' => 'اسم المستند يجب ألا يتجاوز 255 حرف.',
            'client_id.exists' => 'العميل المحدد غير موجود.',
            'category.in' => 'تصنيف المستند غير صالح.',
            'description.max' => 'الوصف يجب ألا يتجاوز 1000 حرف.',
        ];
    }
}
