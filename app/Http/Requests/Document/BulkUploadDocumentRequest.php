<?php

declare(strict_types=1);

namespace App\Http\Requests\Document;

use App\Domain\Document\Enums\DocumentCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkUploadDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => ['file', 'max:20480', 'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,image/jpeg,image/png,image/gif,image/webp,text/plain,text/csv'],
            'client_id' => ['nullable', 'integer', Rule::exists('clients', 'id')->where('tenant_id', app('tenant.id'))],
            'category' => ['nullable', 'string', Rule::in(array_column(DocumentCategory::cases(), 'value'))],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'files.required' => 'يجب رفع ملف واحد على الأقل.',
            'files.min' => 'يجب رفع ملف واحد على الأقل.',
            'files.max' => 'لا يمكن رفع أكثر من 10 ملفات في المرة الواحدة.',
            'files.*.file' => 'يجب أن يكون كل عنصر ملفاً.',
            'files.*.max' => 'حجم كل ملف يجب ألا يتجاوز 20 ميجابايت.',
            'files.*.mimes' => 'نوع الملف غير مسموح به.',
            'client_id.exists' => 'العميل المحدد غير موجود.',
            'category.in' => 'تصنيف المستند غير صالح.',
        ];
    }
}
