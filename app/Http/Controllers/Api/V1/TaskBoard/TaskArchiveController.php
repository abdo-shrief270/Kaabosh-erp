<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Events\TaskUpdated as TaskUpdatedEvent;
use App\Domain\TaskBoard\Models\Task;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaskBoard\TaskResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Manual archive / unarchive on a single task. Archived tasks stay in the
 * DB (soft delete is separate) but are hidden from the default board view
 * and excluded from WIP counts in TaskMovementService.
 */
class TaskArchiveController extends Controller
{
    public function store(Request $request, Task $task): JsonResource
    {
        abort_unless($request->user()?->can('edit_tasks'), 403);

        if (! $task->archived_at) {
            $task->forceFill(['archived_at' => now()])->save();
            TaskUpdatedEvent::dispatch($task->fresh() ?? $task, ['archived_at'], $request->user()?->id);
        }

        return TaskResource::make($task->fresh(['type', 'column', 'primaryAssignee', 'tags', 'versions']));
    }

    public function destroy(Request $request, Task $task): JsonResource
    {
        abort_unless($request->user()?->can('edit_tasks'), 403);

        if ($task->archived_at) {
            $task->forceFill(['archived_at' => null])->save();
            TaskUpdatedEvent::dispatch($task->fresh() ?? $task, ['archived_at'], $request->user()?->id);
        }

        return TaskResource::make($task->fresh(['type', 'column', 'primaryAssignee', 'tags', 'versions']));
    }
}
