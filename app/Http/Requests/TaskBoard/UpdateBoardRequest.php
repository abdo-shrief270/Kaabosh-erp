<?php

declare(strict_types=1);

namespace App\Http\Requests\TaskBoard;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBoardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_boards') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['nullable', 'string', 'max:9'],
            'icon' => ['nullable', 'string', 'max:60'],
            'visibility' => ['sometimes', 'in:private,team,company'],
            'key' => ['nullable', 'string', 'max:12', 'regex:/^[A-Z][A-Z0-9]+$/'],
            'is_default' => ['sometimes', 'boolean'],
            'is_archived' => ['sometimes', 'boolean'],
            'auto_archive_completed_after_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ];
    }
}
