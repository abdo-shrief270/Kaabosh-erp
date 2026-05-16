<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Models\Board;
use App\Domain\TaskBoard\Models\TaskWorkflowTransition;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Per-board, per-task-type column transition rules. The full rule set for
 * a (board, task_type) pair is replaced on each PUT — simpler client UX
 * than diffing edges, and rule lists are small enough that this is cheap.
 */
class WorkflowTransitionController extends Controller
{
    public function index(Request $request, Board $board): JsonResponse
    {
        abort_unless($request->user()?->can('view_tasks'), 403);

        $rows = TaskWorkflowTransition::query()
            ->where('board_id', $board->id)
            ->get(['task_type_id', 'from_column_id', 'to_column_id']);

        // Shape: { task_type_id: [{from, to}, ...] }
        $by = [];
        foreach ($rows as $r) {
            $by[$r->task_type_id] ??= [];
            $by[$r->task_type_id][] = [
                'from' => (int) $r->from_column_id,
                'to' => (int) $r->to_column_id,
            ];
        }

        return response()->json(['data' => $by]);
    }

    public function replace(Request $request, Board $board): JsonResponse
    {
        abort_unless($request->user()?->can('manage_boards'), 403);

        $data = $request->validate([
            'task_type_id' => ['required', 'integer', 'exists:task_types,id,tenant_id,'.app('tenant.id')],
            'edges' => ['present', 'array'],
            'edges.*.from' => ['required', 'integer', 'exists:board_columns,id,board_id,'.$board->id],
            'edges.*.to' => ['required', 'integer', 'exists:board_columns,id,board_id,'.$board->id],
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($board, $data) {
            TaskWorkflowTransition::query()
                ->where('board_id', $board->id)
                ->where('task_type_id', $data['task_type_id'])
                ->delete();

            $rows = [];
            $seen = [];
            foreach ($data['edges'] as $edge) {
                $key = $edge['from'].'->'.$edge['to'];
                if (isset($seen[$key])) continue;
                $seen[$key] = true;
                $rows[] = [
                    'tenant_id' => $board->tenant_id,
                    'board_id' => $board->id,
                    'task_type_id' => $data['task_type_id'],
                    'from_column_id' => $edge['from'],
                    'to_column_id' => $edge['to'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if ($rows) {
                TaskWorkflowTransition::insert($rows);
            }
        });

        return response()->json(['data' => ['task_type_id' => (int) $data['task_type_id'], 'count' => count($data['edges'])]]);
    }
}
