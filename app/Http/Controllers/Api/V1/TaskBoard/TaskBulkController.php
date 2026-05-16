<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Services\TaskMovementService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Bulk operations on a set of tasks. One endpoint, action-discriminated
 * payload, atomic — either every targeted row mutates or none do. Tenant
 * scope is enforced server-side (an outsider id silently drops out of the
 * set after the filter; we never trust the client to scope).
 */
class TaskBulkController extends Controller
{
    private const ACTIONS = ['move', 'assign', 'tag', 'untag', 'version', 'priority', 'archive', 'restore', 'delete', 'add_to_sprint'];

    public function bulk(Request $request, TaskMovementService $movement): JsonResponse
    {
        abort_unless($request->user()?->can('edit_tasks'), 403);

        $tenantId = (int) app('tenant.id');

        $data = $request->validate([
            'action' => ['required', 'string', 'in:'.implode(',', self::ACTIONS)],
            'task_ids' => ['required', 'array', 'min:1', 'max:500'],
            'task_ids.*' => ['integer'],
            // action-specific payload
            'board_column_id' => ['sometimes', 'integer', "exists:board_columns,id,tenant_id,$tenantId"],
            'user_ids' => ['sometimes', 'array'],
            'user_ids.*' => ['integer', "exists:users,id,tenant_id,$tenantId"],
            'tag_ids' => ['sometimes', 'array'],
            'tag_ids.*' => ['integer', "exists:tags,id,tenant_id,$tenantId"],
            'version_ids' => ['sometimes', 'array'],
            'version_ids.*' => ['integer', "exists:versions,id,tenant_id,$tenantId"],
            'priority' => ['sometimes', 'in:low,medium,high,critical'],
            'sprint_id' => ['sometimes', 'integer', "exists:sprints,id,tenant_id,$tenantId"],
        ]);

        $tasks = Task::query()
            ->whereIn('id', $data['task_ids'])
            ->get();

        if ($tasks->isEmpty()) {
            return response()->json(['affected' => 0]);
        }

        $affected = DB::transaction(function () use ($data, $tasks, $movement) {
            return match ($data['action']) {
                'move' => $this->doMove($tasks, $data, $movement),
                'assign' => $this->doAssign($tasks, $data),
                'tag' => $this->doTag($tasks, $data, attach: true),
                'untag' => $this->doTag($tasks, $data, attach: false),
                'version' => $this->doVersion($tasks, $data),
                'add_to_sprint' => $this->doAddToSprint($tasks, $data),
                'priority' => $tasks->each->update(['priority' => $data['priority']])->count(),
                'archive' => $tasks->each->update(['archived_at' => now()])->count(),
                'restore' => $tasks->each->update(['archived_at' => null])->count(),
                'delete' => $tasks->each->delete()->count(),
                default => 0,
            };
        });

        return response()->json([
            'affected' => is_int($affected) ? $affected : $tasks->count(),
            'action' => $data['action'],
        ]);
    }

    /** @param  \Illuminate\Support\Collection<int, Task>  $tasks */
    private function doMove($tasks, array $data, TaskMovementService $movement): int
    {
        abort_if(empty($data['board_column_id']), 422, 'board_column_id required for move.');
        $count = 0;
        foreach ($tasks as $task) {
            $movement->moveTask($task, ['column_id' => $data['board_column_id']]);
            $count++;
        }

        return $count;
    }

    /** @param  \Illuminate\Support\Collection<int, Task>  $tasks */
    private function doAssign($tasks, array $data): int
    {
        $userIds = $data['user_ids'] ?? [];
        $count = 0;
        foreach ($tasks as $task) {
            $pivot = collect($userIds)->mapWithKeys(fn ($id) => [
                $id => ['assigned_by_id' => request()->user()?->id, 'assigned_at' => now()],
            ])->all();
            $task->assignees()->syncWithoutDetaching($pivot);
            if (! $task->primary_assignee_id && $userIds) {
                $task->forceFill(['primary_assignee_id' => $userIds[0]])->save();
            }
            $count++;
        }

        return $count;
    }

    /** @param  \Illuminate\Support\Collection<int, Task>  $tasks */
    private function doTag($tasks, array $data, bool $attach): int
    {
        $tagIds = $data['tag_ids'] ?? [];
        $count = 0;
        foreach ($tasks as $task) {
            $attach ? $task->tags()->syncWithoutDetaching($tagIds) : $task->tags()->detach($tagIds);
            $count++;
        }

        return $count;
    }

    /** @param  \Illuminate\Support\Collection<int, Task>  $tasks */
    private function doVersion($tasks, array $data): int
    {
        $versionIds = $data['version_ids'] ?? [];
        $count = 0;
        foreach ($tasks as $task) {
            $task->versions()->sync($versionIds);
            $count++;
        }

        return $count;
    }

    /** @param  \Illuminate\Support\Collection<int, Task>  $tasks */
    private function doAddToSprint($tasks, array $data): int
    {
        $sprintId = (int) ($data['sprint_id'] ?? 0);
        if ($sprintId === 0) {
            return 0;
        }
        $now = now();
        $actorId = request()->user()?->id;
        $count = 0;
        foreach ($tasks as $task) {
            \Illuminate\Support\Facades\DB::table('sprint_task')->updateOrInsert(
                ['sprint_id' => $sprintId, 'task_id' => $task->id],
                ['added_by_id' => $actorId, 'added_at' => $now],
            );
            $count++;
        }

        return $count;
    }
}
