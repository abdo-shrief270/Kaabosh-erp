<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskTimeEntry;
use App\Domain\TaskBoard\Services\TaskTimerService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskTimerController extends Controller
{
    /** Currently-running timer for the authenticated user, or null. */
    public function current(Request $request, TaskTimerService $timer): JsonResponse
    {
        $entry = $timer->currentFor((int) $request->user()->id);

        return response()->json(['data' => $entry ? $this->shape($entry) : null]);
    }

    public function start(Request $request, Task $task, TaskTimerService $timer): JsonResource
    {
        abort_unless($request->user()?->can('edit_tasks'), 403);

        $data = $request->validate([
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $entry = $timer->start($task, (int) $request->user()->id, $data['description'] ?? null);

        return JsonResource::make($this->shape($entry));
    }

    public function stop(Request $request, TaskTimerService $timer): JsonResource
    {
        $entry = $timer->stop((int) $request->user()->id);

        if (! $entry) {
            return JsonResource::make(null);
        }
        $fresh = $entry->fresh();

        return JsonResource::make($fresh ? $this->shape($fresh) : null);
    }

    public function manualLog(Request $request, Task $task, TaskTimerService $timer): JsonResource
    {
        abort_unless($request->user()?->can('edit_tasks'), 403);

        $data = $request->validate([
            'minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $entry = $timer->manualLog($task, (int) $request->user()->id, $data['minutes'], $data['description'] ?? null);

        return JsonResource::make($this->shape($entry));
    }

    public function index(Request $request, Task $task): JsonResponse
    {
        abort_unless($request->user()?->can('view_tasks'), 403);

        $entries = TaskTimeEntry::query()
            ->where('task_id', $task->id)
            ->with('user:id,name,email')
            ->orderByDesc('started_at')
            ->limit(100)
            ->get();

        $total = (int) $entries->whereNotNull('stopped_at')->sum('duration_seconds');

        return response()->json([
            'data' => [
                'entries' => $entries->map(fn ($e) => $this->shape($e))->all(),
                'total_seconds' => $total,
            ],
        ]);
    }

    public function destroy(Request $request, TaskTimeEntry $taskTimeEntry, TaskTimerService $timer)
    {
        $isOwner = $request->user()?->id === $taskTimeEntry->user_id;
        abort_unless($isOwner || $request->user()?->can('delete_tasks'), 403);

        $task = $taskTimeEntry->task;
        $taskTimeEntry->delete();
        if ($task) {
            $timer->rollupOnto($task);
        }

        return response()->noContent();
    }

    /** @return array<string, mixed> */
    private function shape(TaskTimeEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'task_id' => $entry->task_id,
            'user_id' => $entry->user_id,
            'started_at' => $entry->started_at,
            'stopped_at' => $entry->stopped_at,
            'duration_seconds' => $entry->duration_seconds,
            'is_running' => $entry->stopped_at === null,
            'description' => $entry->description,
            'user' => $entry->relationLoaded('user') && $entry->user ? [
                'id' => $entry->user->id, 'name' => $entry->user->name,
            ] : null,
        ];
    }
}
