<?php

declare(strict_types=1);

namespace App\Http\Requests\TaskBoard;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('edit_tasks') ?? false;
    }

    public function rules(): array
    {
        $tenantId = (int) app('tenant.id');

        return [
            'board_column_id' => ['sometimes', 'integer', "exists:board_columns,id,tenant_id,$tenantId"],
            'task_type_id' => ['sometimes', 'integer', "exists:task_types,id,tenant_id,$tenantId"],
            'parent_task_id' => ['nullable', 'integer', "exists:tasks,id,tenant_id,$tenantId"],
            'title' => ['sometimes', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'priority' => ['sometimes', 'in:low,medium,high,critical'],
            'primary_assignee_id' => ['nullable', 'integer', "exists:users,id,tenant_id,$tenantId"],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'estimate_hours' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'position' => ['nullable', 'numeric'],
            'archived_at' => ['nullable', 'date'],
        ];
    }
}
