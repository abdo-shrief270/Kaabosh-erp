<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Models\Board;
use App\Domain\TaskBoard\Models\BoardColumn;
use App\Domain\TaskBoard\Services\TaskMovementService;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaskBoard\BoardColumnResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class BoardColumnController extends Controller
{
    public function store(Request $request, Board $board): JsonResource
    {
        abort_unless($request->user()?->can('manage_boards'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'color' => ['nullable', 'string', 'max:9'],
            'position' => ['nullable', 'numeric'],
            'wip_limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'enforce_wip' => ['nullable', 'boolean'],
            'requires_approval' => ['nullable', 'boolean'],
            'approver_user_ids' => ['nullable', 'array'],
            'approver_user_ids.*' => ['integer', 'exists:users,id,tenant_id,'.app('tenant.id')],
            'is_done' => ['nullable', 'boolean'],
            'is_initial' => ['nullable', 'boolean'],
        ]);

        $data['position'] ??= ((float) BoardColumn::where('board_id', $board->id)->max('position')) + 1000;
        $data['tenant_id'] = $board->tenant_id;
        $data['board_id'] = $board->id;

        $column = BoardColumn::create($data);

        return BoardColumnResource::make($column);
    }

    public function update(Request $request, BoardColumn $column): JsonResource
    {
        abort_unless($request->user()?->can('manage_boards'), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'color' => ['nullable', 'string', 'max:9'],
            'position' => ['sometimes', 'numeric'],
            'wip_limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'is_done' => ['sometimes', 'boolean'],
        ]);

        $column->update($data);

        return BoardColumnResource::make($column->fresh());
    }

    public function destroy(Request $request, BoardColumn $column)
    {
        abort_unless($request->user()?->can('manage_boards'), 403);

        // Refuse to delete a column that still has tasks; client should move them first.
        if ($column->tasks()->exists()) {
            return response()->json([
                'message' => 'Move tasks out of this column before deleting it.',
            ], 422);
        }

        $column->delete();

        return response()->noContent();
    }

    /**
     * Replace the column order for a board. Payload must list every column id
     * on the board; missing ids are rejected to avoid silent drops.
     */
    public function reorder(Request $request, Board $board, TaskMovementService $movement): AnonymousResourceCollection
    {
        abort_unless($request->user()?->can('manage_boards'), 403);

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $columns = $movement->reorderColumns($board, $data['ids']);

        return BoardColumnResource::collection($columns);
    }
}
