<?php

declare(strict_types=1);

namespace App\Http\Requests\TaskBoard;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create_tasks') ?? false;
    }

    public function rules(): array
    {
        $tenantId = (int) app('tenant.id');

        return [
            'board_id' => ['required', 'integer', "exists:boards,id,tenant_id,$tenantId"],
            'board_column_id' => ['required', 'integer', "exists:board_columns,id,tenant_id,$tenantId"],
            'task_type_id' => ['required', 'integer', "exists:task_types,id,tenant_id,$tenantId"],
            'parent_task_id' => ['nullable', 'integer', "exists:tasks,id,tenant_id,$tenantId"],
            'title' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'priority' => ['nullable', 'in:low,medium,high,critical'],
            'primary_assignee_id' => ['nullable', 'integer', "exists:users,id,tenant_id,$tenantId"],
            'assignee_ids' => ['nullable', 'array'],
            'assignee_ids.*' => ['integer', "exists:users,id,tenant_id,$tenantId"],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', "exists:tags,id,tenant_id,$tenantId"],
            'version_ids' => ['nullable', 'array'],
            'version_ids.*' => ['integer', "exists:versions,id,tenant_id,$tenantId"],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'estimate_hours' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'position' => ['nullable', 'numeric'],
        ];
    }
}
