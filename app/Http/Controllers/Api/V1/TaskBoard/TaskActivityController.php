<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Services\TaskActivityService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskActivityController extends Controller
{
    public function index(Request $request, Task $task, TaskActivityService $activity): JsonResponse
    {
        abort_unless($request->user()?->can('view_tasks'), 403);

        $perPage = (int) min(max($request->integer('per_page', 50), 1), 100);
        $page = $activity->feed($task, $perPage);

        return response()->json([
            'data' => $page->map(fn ($a) => [
                'id' => $a->id,
                'event' => $a->properties['event'] ?? $a->description,
                'description' => $a->description,
                'changes' => [
                    'old' => $a->properties['old'] ?? null,
                    'new' => $a->properties['attributes'] ?? null,
                ],
                'properties' => collect($a->properties)
                    ->except(['old', 'attributes'])
                    ->all(),
                'causer' => $a->causer ? [
                    'id' => $a->causer->id,
                    'name' => $a->causer->name,
                    'email' => $a->causer->email,
                ] : null,
                'created_at' => $a->created_at,
            ])->all(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }
}
