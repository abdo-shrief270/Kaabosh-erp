<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskReminder;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Per-user reminders on a task. A reminder is scoped to the user that
 * created it — it fires only for them, even if the task is shared. The
 * `snooze` shortcut lets the UI bump an existing reminder (or create one)
 * by a relative offset without doing the date math client-side.
 */
class TaskReminderController extends Controller
{
    public function index(Request $request, Task $task): JsonResource
    {
        $userId = (int) $request->user()->id;
        $reminders = TaskReminder::query()
            ->where('task_id', $task->id)
            ->where('user_id', $userId)
            ->orderBy('remind_at')
            ->get();

        return JsonResource::collection($reminders);
    }

    public function store(Request $request, Task $task): JsonResponse
    {
        $data = $request->validate([
            'remind_at' => ['required', 'date', 'after:now'],
            'note' => ['nullable', 'string', 'max:240'],
        ]);

        $reminder = TaskReminder::create([
            'tenant_id' => $task->tenant_id,
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
            'remind_at' => $data['remind_at'],
            'note' => $data['note'] ?? null,
        ]);

        return response()->json(['data' => $reminder], 201);
    }

    /**
     * Snooze shortcut: relative offset from now (or from `remind_at` of the
     * latest reminder if one exists). Cheap path for the "Remind me in 1h"
     * button in the slideover.
     */
    public function snooze(Request $request, Task $task): JsonResponse
    {
        $data = $request->validate([
            'unit' => ['required', 'in:minutes,hours,days,weeks'],
            'value' => ['required', 'integer', 'min:1', 'max:10080'],
            'note' => ['nullable', 'string', 'max:240'],
        ]);

        $userId = (int) $request->user()->id;
        $offset = now()->add($data['unit'], $data['value']);

        $reminder = TaskReminder::create([
            'tenant_id' => $task->tenant_id,
            'task_id' => $task->id,
            'user_id' => $userId,
            'remind_at' => $offset,
            'note' => $data['note'] ?? null,
        ]);

        return response()->json(['data' => $reminder], 201);
    }

    public function destroy(Request $request, TaskReminder $reminder)
    {
        abort_unless($reminder->user_id === (int) $request->user()->id, 403);
        $reminder->delete();

        return response()->noContent();
    }
}
