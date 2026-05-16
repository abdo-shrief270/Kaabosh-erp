<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Models\Task;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TaskShareController extends Controller
{
    /**
     * Generate (or rotate) a share token for a task. Caller can pass an
     * optional `hours` to bound the validity; NULL = permanent until
     * revoked. Returns the public URL the client can copy to clipboard.
     */
    public function create(Request $request, Task $task): JsonResponse
    {
        abort_unless($request->user()?->can('edit_tasks'), 403);

        $data = $request->validate([
            'hours' => ['nullable', 'integer', 'min:1', 'max:8760'], // up to one year
        ]);

        $task->forceFill([
            'share_token' => Str::random(32),
            'shared_until' => isset($data['hours']) ? now()->addHours($data['hours']) : null,
        ])->save();

        $frontend = rtrim((string) config('app.frontend_url', config('app.url')), '/');

        return response()->json([
            'data' => [
                'token' => $task->share_token,
                'shared_until' => $task->shared_until,
                'url' => "$frontend/shared/task/{$task->share_token}",
            ],
        ]);
    }

    public function revoke(Request $request, Task $task)
    {
        abort_unless($request->user()?->can('edit_tasks'), 403);

        $task->forceFill(['share_token' => null, 'shared_until' => null])->save();

        return response()->noContent();
    }

    /**
     * Public, no-auth endpoint. Returns a sanitised view of the task —
     * title / description / type / priority / due date. Excludes comments,
     * attachments, assignees, and anything else identifying.
     */
    public function publicShow(string $token): JsonResponse
    {
        $task = Task::query()
            ->withoutGlobalScopes()
            ->where('share_token', $token)
            ->where(fn ($q) => $q->whereNull('shared_until')->orWhere('shared_until', '>', now()))
            ->with(['type:id,name,icon,color', 'column:id,name,is_done'])
            ->first();

        abort_unless($task, 404);

        return response()->json([
            'data' => [
                'reference' => $task->reference,
                'title' => $task->title,
                'description' => $task->description,
                'priority' => $task->priority?->value,
                'type' => $task->type ? [
                    'name' => $task->type->name,
                    'icon' => $task->type->icon,
                    'color' => $task->type->color,
                ] : null,
                'column' => $task->column ? ['name' => $task->column->name, 'is_done' => $task->column->is_done] : null,
                'due_date' => $task->due_date,
                'completed_at' => $task->completed_at,
                'created_at' => $task->created_at,
            ],
        ]);
    }
}
