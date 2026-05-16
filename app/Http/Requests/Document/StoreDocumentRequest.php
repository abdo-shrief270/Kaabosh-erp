<?php

declare(strict_types=1);

namespace App\Http\Requests\Document;

use App\Domain\Document\Enums\DocumentCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:20480', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,webp,txt,csv,zip,rar'],
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
            'file.required' => 'الملف مطلوب.',
            'file.file' => 'يجب أن يكون الحقل ملفاً.',
            'file.max' => 'حجم الملف يجب ألا يتجاوز 20 ميجابايت.',
            'file.mimes' => 'نوع الملف غير مسموح به.',
            'client_id.exists' => 'العميل المحدد غير موجود.',
            'category.in' => 'تصنيف المستند غير صالح.',
            'description.max' => 'الوصف يجب ألا يتجاوز 1000 حرف.',
        ];
    }
}
