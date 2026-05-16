<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Events\TaskAssigneesChanged;
use App\Domain\TaskBoard\Events\TaskCreated as TaskCreatedEvent;
use App\Domain\TaskBoard\Events\TaskDeleted as TaskDeletedEvent;
use App\Domain\TaskBoard\Events\TaskMoved as TaskMovedEvent;
use App\Domain\TaskBoard\Events\TaskUpdated as TaskUpdatedEvent;
use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Services\TaskActivityService;
use App\Domain\TaskBoard\Services\TaskAssigneeService;
use App\Domain\TaskBoard\Services\TaskMovementService;
use App\Domain\TaskBoard\Services\TaskNotificationDispatcher;
use App\Http\Controllers\Controller;
use App\Http\Requests\TaskBoard\StoreTaskRequest;
use App\Http\Requests\TaskBoard\UpdateTaskRequest;
use App\Http\Resources\TaskBoard\TaskResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $uid = (int) ($request->user()?->id ?? 0);
        $query = Task::query()
            ->with(['type', 'column', 'primaryAssignee', 'tags', 'versions'])
            ->withCount([
                'subtasks', 'comments', 'attachments',
                'checklistItems as checklist_total',
                'checklistItems as checklist_done' => fn ($q) => $q->where('is_done', true),
            ])
            // Per-user star/mute flags as cheap EXISTS sub-selects so listing a
            // 1k-task board doesn't load thousands of pivot rows.
            ->addSelect([
                'is_starred' => \Illuminate\Support\Facades\DB::table('task_stars')
                    ->whereColumn('task_stars.task_id', 'tasks.id')
                    ->where('task_stars.user_id', $uid)
                    ->selectRaw('1')
                    ->limit(1),
                'is_muted' => \Illuminate\Support\Facades\DB::table('task_mutes')
                    ->whereColumn('task_mutes.task_id', 'tasks.id')
                    ->where('task_mutes.user_id', $uid)
                    ->selectRaw('1')
                    ->limit(1),
            ])
            // Active-sprint membership as a correlated sub-select so we don't
            // pay a join across every row. NULL when the task isn't in an
            // active sprint; resource layer hides the chip in that case.
            ->addSelect([
                'current_sprint_id' => \Illuminate\Support\Facades\DB::table('sprint_task')
                    ->join('sprints', 'sprints.id', '=', 'sprint_task.sprint_id')
                    ->whereColumn('sprint_task.task_id', 'tasks.id')
                    ->where('sprints.status', 'active')
                    ->limit(1)
                    ->select('sprints.id'),
                'current_sprint_name' => \Illuminate\Support\Facades\DB::table('sprint_task')
                    ->join('sprints', 'sprints.id', '=', 'sprint_task.sprint_id')
                    ->whereColumn('sprint_task.task_id', 'tasks.id')
                    ->where('sprints.status', 'active')
                    ->limit(1)
                    ->select('sprints.name'),
            ]);

        // Filters — board, column, assignee, priority, type, tag, version, search.
        if ($v = $request->integer('board_id')) {
            $query->where('board_id', $v);
        }
        if ($v = $request->integer('board_column_id')) {
            $query->where('board_column_id', $v);
        }
        if ($v = $request->integer('task_type_id')) {
            $query->where('task_type_id', $v);
        }
        if ($v = $request->integer('parent_task_id')) {
            $query->where('parent_task_id', $v);
        }
        if ($v = $request->integer('assignee_id')) {
            $query->where(function ($q) use ($v) {
                $q->where('primary_assignee_id', $v)
                    ->orWhereHas('assignees', fn ($a) => $a->where('users.id', $v));
            });
        }
        if ($v = $request->string('priority')->toString()) {
            $query->where('priority', $v);
        }
        if ($v = $request->integer('tag_id')) {
            $query->whereHas('tags', fn ($q) => $q->where('tags.id', $v));
        }
        if ($v = $request->integer('version_id')) {
            $query->whereHas('versions', fn ($q) => $q->where('versions.id', $v));
        }
        if ($v = $request->string('q')->toString()) {
            $query->where(fn ($q) => $q->where('title', 'ilike', "%$v%")->orWhere('reference', 'ilike', "%$v%"));
        }
        if ($request->boolean('open_only')) {
            $query->whereNull('completed_at')->whereNull('archived_at');
        }
        if ($request->boolean('overdue')) {
            $query->whereNotNull('due_date')->where('due_date', '<', now())->whereNull('completed_at');
        }
        if ($request->boolean('starred')) {
            $query->whereExists(fn ($q) => $q->selectRaw('1')
                ->from('task_stars')
                ->whereColumn('task_stars.task_id', 'tasks.id')
                ->where('task_stars.user_id', $uid));
        }
        if ($request->boolean('completed_only')) {
            $query->whereNotNull('completed_at');
        }

        // Archive scope: default hides archived tasks. `with_archived=1`
        // shows everything, `archived_only=1` shows only archived. Useful
        // for the SPA's "Show archived" filter and the per-board archive
        // page once we add one.
        if ($request->boolean('archived_only')) {
            $query->whereNotNull('archived_at');
        } elseif (! $request->boolean('with_archived')) {
            $query->whereNull('archived_at');
        }

        // Sort. Default for board view is column+position; list view defaults to recent.
        $sort = $request->string('sort', 'recent')->toString();
        match ($sort) {
            'board' => $query->orderBy('board_column_id')->orderBy('position'),
            'due' => $query->orderByRaw('due_date IS NULL, due_date ASC'),
            'priority' => $query->orderByRaw("CASE priority
                WHEN 'critical' THEN 4 WHEN 'high' THEN 3 WHEN 'medium' THEN 2 WHEN 'low' THEN 1 END DESC"),
            default => $query->orderByDesc('created_at'),
        };

        $perPage = (int) min(max($request->integer('per_page', 50), 1), 200);

        return TaskResource::collection($query->paginate($perPage));
    }

    public function show(Request $request, Task $task): JsonResource
    {
        $uid = (int) ($request->user()?->id ?? 0);
        $task->setAttribute('is_starred', \Illuminate\Support\Facades\DB::table('task_stars')
            ->where('task_id', $task->id)->where('user_id', $uid)->exists() ? 1 : null);
        $task->setAttribute('is_muted', \Illuminate\Support\Facades\DB::table('task_mutes')
            ->where('task_id', $task->id)->where('user_id', $uid)->exists() ? 1 : null);

        // Recursive subtask eager-load: load `subtasks.subtasks.subtasks…` up
        // to 6 levels (deeper trees are real but vanishingly rare). Each
        // level also pulls its type so the UI can render the icon without a
        // per-subtask round-trip.
        $recursive = [];
        $chain = '';
        for ($i = 0; $i < 6; $i++) {
            $chain .= ($chain === '' ? '' : '.').'subtasks';
            $recursive[] = $chain.'.type';
            $recursive[] = $chain.'.primaryAssignee';
            $recursive[] = $chain.'.column';
        }

        $task->load(array_merge([
            'type', 'column', 'primaryAssignee', 'assignees',
            'tags', 'versions', 'parent', 'checklists.items', 'reactions',
            'customFieldValues',
        ], $recursive))->loadCount(['comments', 'attachments', 'subtasks']);

        return TaskResource::make($task);
    }

    public function store(StoreTaskRequest $request): JsonResource
    {
        $data = $request->validated();
        $data['reporter_id'] ??= $request->user()?->id;
        $data['priority'] ??= 'medium';

        $task = DB::transaction(function () use ($data) {
            $assignees = $data['assignee_ids'] ?? [];
            $tags = $data['tag_ids'] ?? [];
            $versions = $data['version_ids'] ?? [];
            unset($data['assignee_ids'], $data['tag_ids'], $data['version_ids']);

            /** @var Task $task */
            $task = Task::create($data);

            if ($assignees) {
                $pivot = collect($assignees)->mapWithKeys(fn ($id) => [
                    $id => ['assigned_by_id' => request()->user()?->id, 'assigned_at' => now()],
                ])->all();
                $task->assignees()->sync($pivot);
                if (! $task->primary_assignee_id) {
                    $task->forceFill(['primary_assignee_id' => $assignees[0]])->save();
                }
            }
            if ($tags) {
                $task->tags()->sync($tags);
            }
            if ($versions) {
                $task->versions()->sync($versions);
            }

            return $task;
        });

        TaskCreatedEvent::dispatch($task, $request->user()?->id);

        return TaskResource::make($task->load(['type', 'column', 'primaryAssignee', 'tags', 'versions']));
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResource
    {
        $data = $request->validated();
        $task->update($data);

        TaskUpdatedEvent::dispatch(
            $task->fresh() ?? $task,
            array_keys($data),
            $request->user()?->id,
        );

        return TaskResource::make($task->fresh(['type', 'column', 'primaryAssignee', 'tags', 'versions']));
    }

    public function destroy(Request $request, Task $task)
    {
        $taskId = $task->id;
        $boardId = $task->board_id;
        $task->delete();

        TaskDeletedEvent::dispatch($taskId, $boardId, $request->user()?->id);

        return response()->noContent();
    }

    /**
     * Move a task into a column at a precise position.
     * Three anchor modes (priority order): before_task_id → after_task_id → index.
     * With none of those, the task is appended to the column.
     */
    public function move(
        Request $request,
        Task $task,
        TaskMovementService $movement,
        TaskActivityService $activity,
        TaskNotificationDispatcher $notify,
    ): JsonResource {
        abort_unless($request->user()?->can('edit_tasks'), 403);

        $data = $request->validate([
            'board_column_id' => ['required', 'integer', 'exists:board_columns,id,tenant_id,'.app('tenant.id')],
            'before_task_id' => ['nullable', 'integer', 'exists:tasks,id,tenant_id,'.app('tenant.id')],
            'after_task_id' => ['nullable', 'integer', 'exists:tasks,id,tenant_id,'.app('tenant.id')],
            'index' => ['nullable', 'integer', 'min:0'],
        ]);

        $fromColumnId = $task->board_column_id;

        $moved = $movement->moveTask($task, [
            'column_id' => $data['board_column_id'],
            'before_task_id' => $data['before_task_id'] ?? null,
            'after_task_id' => $data['after_task_id'] ?? null,
            'index' => $data['index'] ?? null,
        ]);

        $activity->moved($moved, (int) $request->user()->id, $fromColumnId, (int) $data['board_column_id']);
        $notify->taskMoved($moved, $fromColumnId, (int) $data['board_column_id'], (int) $request->user()->id);

        TaskMovedEvent::dispatch(
            $moved,
            $fromColumnId,
            (int) $data['board_column_id'],
            (bool) $moved->completed_at,
            $request->user()?->id,
        );

        return TaskResource::make($moved->load(['type', 'column']));
    }

    /**
     * Move a task to a column on a DIFFERENT board. Detaches sprint/tag/
     * version memberships, drops cross-board dependencies, regenerates
     * the reference under the new board's key. Subtasks are not yet
     * supported — refuses tasks that have children.
     */
    public function moveCrossBoard(
        Request $request,
        Task $task,
        TaskMovementService $movement,
    ): JsonResource {
        abort_unless($request->user()?->can('edit_tasks'), 403);

        $data = $request->validate([
            'board_column_id' => ['required', 'integer', 'exists:board_columns,id,tenant_id,'.app('tenant.id')],
        ]);

        $fromBoardId = (int) $task->board_id;
        $fromColumnId = (int) $task->board_column_id;
        $column = \App\Domain\TaskBoard\Models\BoardColumn::query()
            ->whereKey($data['board_column_id'])
            ->firstOrFail();

        $moved = $movement->moveTaskCrossBoard($task, $column);

        TaskMovedEvent::dispatch(
            $moved,
            $fromColumnId,
            (int) $moved->board_column_id,
            (bool) $moved->completed_at,
            $request->user()?->id,
        );
        // Also dispatch a delete for the old board so realtime watchers
        // there drop the task; the new board's `task.created` echo could
        // be wired off the TaskMoved event but is intentionally skipped
        // to keep the cross-board path explicit.
        \App\Domain\TaskBoard\Events\TaskDeleted::dispatch(
            $moved->id, $fromBoardId, $request->user()?->id,
        );
        \App\Domain\TaskBoard\Events\TaskCreated::dispatch(
            $moved, $request->user()?->id,
        );

        return TaskResource::make($moved->load(['type', 'column', 'primaryAssignee', 'tags', 'versions']));
    }

    public function syncAssignees(
        Request $request,
        Task $task,
        TaskAssigneeService $svc,
        TaskActivityService $activity,
        TaskNotificationDispatcher $notify,
    ): JsonResource {
        abort_unless($request->user()?->can('assign_tasks'), 403);

        $data = $request->validate([
            'user_ids' => ['present', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id,tenant_id,'.app('tenant.id')],
        ]);

        $before = $task->assignees()->pluck('users.id')->map(fn ($v) => (int) $v)->all();
        $after = $svc->syncAssignees($task, $data['user_ids'], $request->user()?->id);

        $added = array_values(array_diff($after, $before));
        $activity->assigneesChanged(
            task: $task,
            userId: (int) $request->user()->id,
            added: $added,
            removed: array_values(array_diff($before, $after)),
        );
        $notify->assigneesAdded($task, $added, (int) $request->user()->id);

        TaskAssigneesChanged::dispatch(
            $task,
            $added,
            array_values(array_diff($before, $after)),
            $request->user()?->id,
        );

        return TaskResource::make($task->fresh(['assignees', 'primaryAssignee']));
    }

    public function setPrimaryAssignee(Request $request, Task $task, TaskAssigneeService $svc): JsonResource
    {
        abort_unless($request->user()?->can('assign_tasks'), 403);

        $data = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id,tenant_id,'.app('tenant.id')],
        ]);

        $svc->setPrimaryAssignee($task, $data['user_id'] ?? null, $request->user()?->id);

        return TaskResource::make($task->fresh(['assignees', 'primaryAssignee']));
    }

    public function watch(Request $request, Task $task, TaskAssigneeService $svc, TaskActivityService $activity)
    {
        abort_unless($request->user()?->can('view_tasks'), 403);
        $svc->watch($task, (int) $request->user()->id);
        $activity->watch($task, (int) $request->user()->id, true);

        return response()->json(['watching' => true]);
    }

    public function unwatch(Request $request, Task $task, TaskAssigneeService $svc, TaskActivityService $activity)
    {
        $svc->unwatch($task, (int) $request->user()->id);
        $activity->watch($task, (int) $request->user()->id, false);

        return response()->json(['watching' => false]);
    }

    /**
     * Helper to create a subtask under an existing task. Delegates to the
     * main store() pipeline so all validation, observers and pivot wiring
     * apply the same way.
     */
    public function storeSubtask(StoreTaskRequest $request, Task $task): JsonResource
    {
        $request->merge([
            'board_id' => $task->board_id,
            'parent_task_id' => $task->id,
            'board_column_id' => $request->integer('board_column_id') ?: $task->board_column_id,
        ]);

        return $this->store($request);
    }
}
