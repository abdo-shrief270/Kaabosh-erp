<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskDependency;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskDependencyController extends Controller
{
    /**
     * Returns both directions for the slideover:
     *   - outgoing: rows where this task is the actor (it blocks / duplicates / relates_to others)
     *   - incoming: rows where another task targets this one (it is blocked by / duplicated by …)
     */
    public function index(Request $request, Task $task): JsonResource
    {
        abort_unless($request->user()?->can('view_tasks'), 403);

        $outgoing = $task->dependencies()->with(['dependsOn:id,board_id,reference,number,title,task_type_id,completed_at', 'dependsOn.type'])->get();
        $incoming = $task->dependents()->with(['task:id,board_id,reference,number,title,task_type_id,completed_at', 'task.type'])->get();

        return JsonResource::make([
            'outgoing' => $outgoing->map(fn ($d) => $this->shape($d, $d->dependsOn, $d->kind, direction: 'outgoing'))->all(),
            'incoming' => $incoming->map(fn ($d) => $this->shape($d, $d->task, $d->kind, direction: 'incoming'))->all(),
        ]);
    }

    public function store(Request $request, Task $task): JsonResource
    {
        abort_unless($request->user()?->can('edit_tasks'), 403);

        $tenantId = (int) app('tenant.id');
        $data = $request->validate([
            'depends_on_task_id' => ['required', 'integer', "exists:tasks,id,tenant_id,$tenantId"],
            'kind' => ['required', 'in:'.implode(',', TaskDependency::KINDS)],
        ]);

        if ($data['depends_on_task_id'] === $task->id) {
            return response()->json(['message' => 'A task cannot depend on itself.'], 422)
                ->setStatusCode(422);
        }

        // For 'blocks', refuse the cycle: if the target already blocks this
        // task (directly), creating the inverse forms a 2-cycle. Deeper
        // cycle detection is doable with a CTE but rare in practice.
        if ($data['kind'] === TaskDependency::KIND_BLOCKS) {
            $cycle = TaskDependency::query()
                ->where('task_id', $data['depends_on_task_id'])
                ->where('depends_on_task_id', $task->id)
                ->where('kind', TaskDependency::KIND_BLOCKS)
                ->exists();
            if ($cycle) {
                return response()->json(['message' => 'That task already blocks this one — cycle refused.'], 422);
            }
        }

        $row = TaskDependency::firstOrCreate(
            ['task_id' => $task->id, 'depends_on_task_id' => $data['depends_on_task_id'], 'kind' => $data['kind']],
            ['tenant_id' => $task->tenant_id, 'created_by' => $request->user()?->id],
        );

        return JsonResource::make($row->load(['dependsOn.type']));
    }

    public function destroy(Request $request, TaskDependency $taskDependency)
    {
        abort_unless($request->user()?->can('edit_tasks'), 403);
        $taskDependency->delete();

        return response()->noContent();
    }

    /** @return array<string, mixed> */
    private function shape(TaskDependency $dep, ?Task $other, string $kind, string $direction): array
    {
        return [
            'id' => $dep->id,
            'kind' => $kind,
            'direction' => $direction,
            'task' => $other ? [
                'id' => $other->id,
                'reference' => $other->reference,
                'title' => $other->title,
                'board_id' => $other->board_id,
                'task_type_id' => $other->task_type_id,
                'completed_at' => $other->completed_at,
                'type' => $other->type ? [
                    'id' => $other->type->id,
                    'name' => $other->type->name,
                    'icon' => $other->type->icon,
                    'color' => $other->type->color,
                ] : null,
            ] : null,
        ];
    }
}
