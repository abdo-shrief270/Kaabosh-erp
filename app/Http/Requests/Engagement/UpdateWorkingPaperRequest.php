<?php

declare(strict_types=1);

namespace App\Http\Requests\Engagement;

use App\Domain\Engagement\Enums\WorkingPaperStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkingPaperRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'section' => ['sometimes', 'string', 'max:50'],
            'reference_code' => ['nullable', 'string', 'max:30'],
            'title_ar' => ['sometimes', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['sometimes', Rule::enum(WorkingPaperStatus::class)],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'document_id' => ['nullable', 'integer', Rule::exists('documents', 'id')],
            'notes' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
