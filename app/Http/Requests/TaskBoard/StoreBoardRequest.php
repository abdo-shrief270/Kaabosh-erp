<?php

declare(strict_types=1);

namespace App\Http\Requests\TaskBoard;

use Illuminate\Foundation\Http\FormRequest;

class StoreBoardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_boards') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9-]+$/'],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['nullable', 'string', 'max:9'],
            'icon' => ['nullable', 'string', 'max:60'],
            'visibility' => ['nullable', 'in:private,team,company'],
            'key' => ['nullable', 'string', 'max:12', 'regex:/^[A-Z][A-Z0-9]+$/'],
            'is_default' => ['nullable', 'boolean'],
            'columns' => ['nullable', 'array', 'min:1', 'max:20'],
            'columns.*.name' => ['required_with:columns', 'string', 'max:120'],
            'columns.*.color' => ['nullable', 'string', 'max:9'],
            'columns.*.wip_limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'columns.*.is_done' => ['nullable', 'boolean'],
            'columns.*.is_initial' => ['nullable', 'boolean'],
        ];
    }
}
