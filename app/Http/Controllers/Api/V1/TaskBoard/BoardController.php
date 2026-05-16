<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Models\Board;
use App\Domain\TaskBoard\Models\BoardColumn;
use App\Http\Controllers\Controller;
use App\Http\Requests\TaskBoard\StoreBoardRequest;
use App\Http\Requests\TaskBoard\UpdateBoardRequest;
use App\Http\Resources\TaskBoard\BoardResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BoardController extends Controller
{
    public function __construct(
        private readonly \App\Domain\TaskBoard\Services\BoardAccessService $access,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $accessibleIds = $this->access->accessibleBoardIds($request->user());

        $query = Board::query()
            ->whereIn('id', $accessibleIds)
            ->withCount('tasks')
            ->when($request->boolean('include_archived') === false, fn ($q) => $q->where('is_archived', false))
            ->orderBy('is_default', 'desc')
            ->orderBy('name');

        return BoardResource::collection($query->get());
    }

    public function show(Request $request, Board $board): JsonResource
    {
        abort_unless($this->access->has($board, $request->user(), \App\Domain\TaskBoard\Models\BoardMember::LEVEL_VIEWER), 403);

        $board->load(['columns' => fn ($q) => $q->orderBy('position')])
            ->loadCount('tasks');

        return BoardResource::make($board);
    }

    public function store(StoreBoardRequest $request): JsonResource
    {
        $data = $request->validated();
        $columns = $data['columns'] ?? [
            ['name' => 'To Do', 'is_initial' => true,  'color' => '#94a3b8'],
            ['name' => 'In Progress',                  'color' => '#3b82f6'],
            ['name' => 'Done',  'is_done' => true,    'color' => '#10b981'],
        ];
        unset($data['columns']);

        $data['slug'] ??= Str::slug($data['name']).'-'.Str::random(6);
        $data['created_by'] = $request->user()?->id;

        $board = DB::transaction(function () use ($data, $columns) {
            /** @var Board $board */
            $board = Board::create($data);

            foreach (array_values($columns) as $i => $col) {
                BoardColumn::create([
                    'tenant_id' => $board->tenant_id,
                    'board_id' => $board->id,
                    'name' => $col['name'],
                    'color' => $col['color'] ?? null,
                    'position' => ($i + 1) * 1000,
                    'wip_limit' => $col['wip_limit'] ?? null,
                    'is_done' => (bool) ($col['is_done'] ?? false),
                    'is_initial' => (bool) ($col['is_initial'] ?? ($i === 0)),
                ]);
            }

            return $board;
        });

        return BoardResource::make($board->load('columns'));
    }

    public function update(UpdateBoardRequest $request, Board $board): JsonResource
    {
        abort_unless($this->access->has($board, $request->user(), \App\Domain\TaskBoard\Models\BoardMember::LEVEL_ADMIN), 403);

        $board->update($request->validated());

        return BoardResource::make($board->fresh('columns'));
    }

    public function destroy(Request $request, Board $board)
    {
        abort_unless($this->access->has($board, $request->user(), \App\Domain\TaskBoard\Models\BoardMember::LEVEL_ADMIN), 403);

        $board->delete();

        return response()->noContent();
    }

    /**
     * Enable / disable email-to-task ingestion for this board. Generating
     * a new key rotates the inbox address — useful if it leaks.
     */
    public function toggleInbox(\Illuminate\Http\Request $request, Board $board): JsonResource
    {
        abort_unless($request->user()?->can('manage_boards'), 403);

        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'rotate' => ['nullable', 'boolean'],
        ]);

        if ($data['enabled']) {
            if (! $board->inbox_key || ($data['rotate'] ?? false)) {
                $board->inbox_key = \Illuminate\Support\Str::random(16);
            }
            $board->inbox_enabled = true;
        } else {
            $board->inbox_enabled = false;
        }
        $board->save();

        return BoardResource::make($board->fresh());
    }
}
