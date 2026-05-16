<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Models\TaskType;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaskBoard\TaskTypeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class TaskTypeController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return TaskTypeResource::collection(TaskType::orderBy('name')->get());
    }

    public function store(Request $request): JsonResource
    {
        abort_unless($request->user()?->can('manage_task_types'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'icon' => ['nullable', 'string', 'max:60'],
            'color' => ['nullable', 'string', 'max:9'],
            'is_subtask' => ['nullable', 'boolean'],
            'is_epic' => ['nullable', 'boolean'],
        ]);

        $data['slug'] = Str::slug($data['name']);
        $data['is_system'] = false;

        $type = TaskType::create($data);

        return TaskTypeResource::make($type);
    }

    public function update(Request $request, TaskType $taskType): JsonResource
    {
        abort_unless($request->user()?->can('manage_task_types'), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:80'],
            'icon' => ['nullable', 'string', 'max:60'],
            'color' => ['nullable', 'string', 'max:9'],
        ]);

        $taskType->update($data);

        return TaskTypeResource::make($taskType->fresh());
    }

    public function destroy(Request $request, TaskType $taskType)
    {
        abort_unless($request->user()?->can('manage_task_types'), 403);

        if ($taskType->is_system) {
            return response()->json(['message' => 'Built-in types cannot be deleted.'], 422);
        }
        if ($taskType->tasks()->exists()) {
            return response()->json(['message' => 'Reassign tasks before deleting this type.'], 422);
        }

        $taskType->delete();

        return response()->noContent();
    }
}
