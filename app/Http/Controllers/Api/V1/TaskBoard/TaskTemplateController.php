<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskTemplate;
use App\Domain\TaskBoard\Services\TaskTemplateService;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaskBoard\TaskResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskTemplateController extends Controller
{
    public function index(Request $request): JsonResource
    {
        abort_unless($request->user()?->can('view_tasks'), 403);

        $query = TaskTemplate::query()->orderByDesc('use_count')->orderBy('name');
        if ($boardId = $request->integer('board_id')) {
            $query->where(fn ($q) => $q->where('board_id', $boardId)->orWhereNull('board_id'));
        }

        return JsonResource::collection($query->get());
    }

    public function store(Request $request, TaskTemplateService $service): JsonResource
    {
        abort_unless($request->user()?->can('manage_boards'), 403);

        $tenantId = (int) app('tenant.id');
        $data = $request->validate([
            // Two creation paths: from a task snapshot, or fully manual.
            'from_task_id' => ['nullable', 'integer', "exists:tasks,id,tenant_id,$tenantId"],
            'board_id' => ['nullable', 'integer', "exists:boards,id,tenant_id,$tenantId"],
            'name' => ['required', 'string', 'max:120'],
            'icon' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:500'],
            'task_type_id' => ['nullable', 'integer', "exists:task_types,id,tenant_id,$tenantId"],
            'title_template' => ['nullable', 'string', 'max:500'],
            'body_template' => ['nullable', 'string'],
            'priority' => ['nullable', 'in:low,medium,high,critical'],
            'default_estimate_hours' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'default_tag_ids' => ['nullable', 'array'],
            'default_tag_ids.*' => ['integer'],
            'default_checklist' => ['nullable', 'array'],
            'default_checklist.*' => ['string', 'max:500'],
        ]);

        if (! empty($data['from_task_id'])) {
            $task = Task::query()->whereKey($data['from_task_id'])->firstOrFail();
            $template = $service->createFromTask(
                task: $task,
                name: $data['name'],
                boardScopeId: $data['board_id'] ?? null,
                createdBy: $request->user()?->id,
            );
            return JsonResource::make($template);
        }

        $template = TaskTemplate::create([
            'tenant_id' => $tenantId,
            'board_id' => $data['board_id'] ?? null,
            'task_type_id' => $data['task_type_id'] ?? null,
            'created_by' => $request->user()?->id,
            'name' => $data['name'],
            'icon' => $data['icon'] ?? null,
            'description' => $data['description'] ?? null,
            'title_template' => $data['title_template'] ?? $data['name'],
            'body_template' => $data['body_template'] ?? null,
            'priority' => $data['priority'] ?? 'medium',
            'default_estimate_hours' => $data['default_estimate_hours'] ?? null,
            'default_tag_ids' => $data['default_tag_ids'] ?? null,
            'default_checklist' => $data['default_checklist'] ?? null,
        ]);

        return JsonResource::make($template);
    }

    public function update(Request $request, TaskTemplate $taskTemplate): JsonResource
    {
        abort_unless($request->user()?->can('manage_boards'), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'icon' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:500'],
            'title_template' => ['sometimes', 'string', 'max:500'],
            'body_template' => ['nullable', 'string'],
            'priority' => ['sometimes', 'in:low,medium,high,critical'],
            'default_estimate_hours' => ['nullable', 'numeric', 'min:0'],
            'default_tag_ids' => ['nullable', 'array'],
            'default_checklist' => ['nullable', 'array'],
        ]);
        $taskTemplate->update($data);

        return JsonResource::make($taskTemplate->fresh());
    }

    public function destroy(Request $request, TaskTemplate $taskTemplate)
    {
        abort_unless($request->user()?->can('manage_boards'), 403);
        if ($taskTemplate->is_system) {
            return response()->json(['message' => 'Built-in templates cannot be deleted.'], 422);
        }
        $taskTemplate->delete();
        return response()->noContent();
    }

    /** Spawn a task from this template into the requested column. */
    public function spawn(Request $request, TaskTemplate $taskTemplate, TaskTemplateService $service): JsonResource
    {
        abort_unless($request->user()?->can('create_tasks'), 403);

        $tenantId = (int) app('tenant.id');
        $data = $request->validate([
            'board_id' => ['required', 'integer', "exists:boards,id,tenant_id,$tenantId"],
            'board_column_id' => ['required', 'integer', "exists:board_columns,id,tenant_id,$tenantId"],
            'title_override' => ['nullable', 'string', 'max:500'],
        ]);

        $task = $service->spawn(
            template: $taskTemplate,
            boardId: $data['board_id'],
            boardColumnId: $data['board_column_id'],
            override: array_filter([
                'title' => $data['title_override'] ?? null,
                'reporter_id' => $request->user()?->id,
            ]),
        );

        return TaskResource::make($task->load(['type', 'column', 'primaryAssignee', 'tags']));
    }
}
