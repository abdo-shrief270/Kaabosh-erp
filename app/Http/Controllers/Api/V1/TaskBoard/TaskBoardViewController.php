<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Models\TaskBoardView;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskBoardViewController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        abort_unless($request->user()?->can('view_tasks'), 403);

        $userId = (int) $request->user()->id;
        $query = TaskBoardView::query()
            ->where(fn ($q) => $q->where('user_id', $userId)->orWhere('is_shared', true))
            ->orderByDesc('is_pinned')
            ->orderBy('position')
            ->orderBy('name');

        if ($boardId = $request->integer('board_id')) {
            $query->where('board_id', $boardId);
        }

        return JsonResource::collection($query->get());
    }

    public function store(Request $request): JsonResource
    {
        abort_unless($request->user()?->can('view_tasks'), 403);

        $tenantId = (int) app('tenant.id');
        $data = $request->validate([
            'board_id' => ['required', 'integer', "exists:boards,id,tenant_id,$tenantId"],
            'name' => ['required', 'string', 'max:120'],
            'view_mode' => ['nullable', 'in:kanban,list,calendar'],
            'filters' => ['nullable', 'array'],
            'is_shared' => ['nullable', 'boolean'],
            'is_pinned' => ['nullable', 'boolean'],
        ]);

        $view = TaskBoardView::create([
            'tenant_id' => $tenantId,
            'board_id' => $data['board_id'],
            'user_id' => $request->user()->id,
            'name' => $data['name'],
            'view_mode' => $data['view_mode'] ?? 'kanban',
            'filters' => $data['filters'] ?? null,
            'is_shared' => (bool) ($data['is_shared'] ?? false),
            'is_pinned' => (bool) ($data['is_pinned'] ?? false),
            'position' => 0,
        ]);

        return JsonResource::make($view);
    }

    public function update(Request $request, TaskBoardView $taskBoardView): JsonResource
    {
        // Only the owner can edit. (Shared views are read-only to others.)
        abort_unless($request->user()?->id === $taskBoardView->user_id, 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'view_mode' => ['sometimes', 'in:kanban,list,calendar'],
            'filters' => ['nullable', 'array'],
            'is_shared' => ['sometimes', 'boolean'],
            'is_pinned' => ['sometimes', 'boolean'],
            'position' => ['sometimes', 'numeric'],
        ]);

        $taskBoardView->update($data);

        return JsonResource::make($taskBoardView->fresh());
    }

    public function destroy(Request $request, TaskBoardView $taskBoardView)
    {
        abort_unless($request->user()?->id === $taskBoardView->user_id, 403);
        $taskBoardView->delete();

        return response()->noContent();
    }
}
