<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskReaction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskReactionController extends Controller
{
    public function toggle(Request $request, Task $task): JsonResponse
    {
        abort_unless($request->user()?->can('comment_tasks') || $request->user()?->can('view_tasks'), 403);

        $data = $request->validate([
            'emoji' => ['required', 'string', 'max:16'],
        ]);

        $userId = (int) $request->user()->id;
        $existing = TaskReaction::query()
            ->where('task_id', $task->id)
            ->where('user_id', $userId)
            ->where('emoji', $data['emoji'])
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            TaskReaction::create([
                'tenant_id' => $task->tenant_id,
                'task_id' => $task->id,
                'user_id' => $userId,
                'emoji' => $data['emoji'],
                'created_at' => now(),
            ]);
        }

        return response()->json(['data' => $this->summarise($task, $userId)]);
    }

    /** @return array<int, array<string, mixed>> */
    private function summarise(Task $task, int $currentUserId): array
    {
        $rows = TaskReaction::query()->where('task_id', $task->id)->get(['emoji', 'user_id']);
        $grouped = [];
        foreach ($rows as $r) {
            $grouped[$r->emoji] ??= ['emoji' => $r->emoji, 'count' => 0, 'mine' => false, 'user_ids' => []];
            $grouped[$r->emoji]['count']++;
            $grouped[$r->emoji]['user_ids'][] = (int) $r->user_id;
            if ((int) $r->user_id === $currentUserId) {
                $grouped[$r->emoji]['mine'] = true;
            }
        }
        return array_values($grouped);
    }
}
