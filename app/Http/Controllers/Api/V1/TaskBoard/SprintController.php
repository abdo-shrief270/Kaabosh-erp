<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Models\Sprint;
use App\Domain\TaskBoard\Services\SprintService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SprintController extends Controller
{
    public function index(Request $request): JsonResource
    {
        abort_unless($request->user()?->can('view_tasks'), 403);

        $query = Sprint::query()->withCount('tasks')->orderByDesc('starts_at');
        if ($boardId = $request->integer('board_id')) {
            $query->where('board_id', $boardId);
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        return JsonResource::collection($query->get());
    }

    public function active(Request $request, SprintService $service): JsonResponse
    {
        abort_unless($request->user()?->can('view_tasks'), 403);
        $boardId = $request->integer('board_id');
        return response()->json(['data' => $boardId ? $service->activeStats($boardId) : null]);
    }

    public function show(Request $request, Sprint $sprint): JsonResource
    {
        abort_unless($request->user()?->can('view_tasks'), 403);

        $sprint->load([
            'tasks.type', 'tasks.column', 'tasks.primaryAssignee',
            'snapshots',
        ]);

        return JsonResource::make($sprint);
    }

    public function store(Request $request): JsonResource
    {
        abort_unless($request->user()?->can('manage_boards'), 403);

        $tenantId = (int) app('tenant.id');
        $data = $request->validate([
            'board_id' => ['required', 'integer', "exists:boards,id,tenant_id,$tenantId"],
            'name' => ['required', 'string', 'max:120'],
            'goal' => ['nullable', 'string', 'max:500'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'task_ids' => ['nullable', 'array'],
            'task_ids.*' => ['integer', "exists:tasks,id,tenant_id,$tenantId"],
        ]);

        /** @var Sprint $sprint */
        $sprint = Sprint::create([
            'tenant_id' => $tenantId,
            'board_id' => $data['board_id'],
            'name' => $data['name'],
            'goal' => $data['goal'] ?? null,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'created_by' => $request->user()?->id,
        ]);

        if (! empty($data['task_ids'])) {
            $now = now();
            $pivot = collect($data['task_ids'])->mapWithKeys(fn ($id) => [
                $id => ['added_by_id' => $request->user()?->id, 'added_at' => $now],
            ])->all();
            $sprint->tasks()->sync($pivot);
        }

        return JsonResource::make($sprint->load('tasks'));
    }

    public function update(Request $request, Sprint $sprint): JsonResource
    {
        abort_unless($request->user()?->can('manage_boards'), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'goal' => ['nullable', 'string', 'max:500'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'date', 'after:starts_at'],
        ]);
        $sprint->update($data);

        return JsonResource::make($sprint->fresh());
    }

    public function destroy(Request $request, Sprint $sprint)
    {
        abort_unless($request->user()?->can('manage_boards'), 403);
        $sprint->delete();
        return response()->noContent();
    }

    public function start(Request $request, Sprint $sprint, SprintService $service): JsonResource
    {
        abort_unless($request->user()?->can('manage_boards'), 403);
        return JsonResource::make($service->start($sprint));
    }

    public function complete(Request $request, Sprint $sprint, SprintService $service): JsonResource
    {
        abort_unless($request->user()?->can('manage_boards'), 403);
        $data = $request->validate([
            'rollover_to_sprint_id' => ['nullable', 'integer'],
        ]);
        return JsonResource::make($service->complete($sprint, $data['rollover_to_sprint_id'] ?? null));
    }

    public function addTasks(Request $request, Sprint $sprint): JsonResource
    {
        abort_unless($request->user()?->can('manage_boards'), 403);
        $tenantId = (int) app('tenant.id');
        $data = $request->validate([
            'task_ids' => ['required', 'array', 'min:1'],
            'task_ids.*' => ['integer', "exists:tasks,id,tenant_id,$tenantId"],
        ]);
        $now = now();
        $pivot = collect($data['task_ids'])->mapWithKeys(fn ($id) => [
            $id => ['added_by_id' => $request->user()?->id, 'added_at' => $now],
        ])->all();
        $sprint->tasks()->syncWithoutDetaching($pivot);

        return JsonResource::make($sprint->fresh('tasks'));
    }

    public function removeTask(Request $request, Sprint $sprint, int $task): JsonResource
    {
        abort_unless($request->user()?->can('manage_boards'), 403);
        $sprint->tasks()->detach($task);
        return JsonResource::make($sprint->fresh('tasks'));
    }

    public function burndown(Request $request, Sprint $sprint): JsonResource
    {
        abort_unless($request->user()?->can('view_tasks'), 403);
        return JsonResource::collection($sprint->snapshots()->get());
    }
}
