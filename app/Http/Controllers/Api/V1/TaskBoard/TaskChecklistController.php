<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskChecklist;
use App\Domain\TaskBoard\Models\TaskChecklistItem;
use App\Domain\TaskBoard\Services\TaskActivityService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskChecklistController extends Controller
{
    public function index(Request $request, Task $task): JsonResource
    {
        abort_unless($request->user()?->can('view_tasks'), 403);

        $checklists = $task->checklists()->with(['items.completedBy:id,name'])->get();

        return JsonResource::collection($checklists);
    }

    public function store(Request $request, Task $task): JsonResource
    {
        abort_unless($request->user()?->can('edit_tasks'), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'items' => ['nullable', 'array'],
            'items.*.text' => ['required_with:items', 'string', 'max:1000'],
        ]);

        $position = ((float) $task->checklists()->max('position')) + 1000;
        /** @var TaskChecklist $checklist */
        $checklist = TaskChecklist::create([
            'task_id' => $task->id,
            'title' => $data['title'],
            'position' => $position,
        ]);

        foreach (($data['items'] ?? []) as $i => $item) {
            TaskChecklistItem::create([
                'checklist_id' => $checklist->id,
                'text' => $item['text'],
                'position' => ($i + 1) * 1000,
            ]);
        }

        return JsonResource::make($checklist->load('items'));
    }

    public function update(Request $request, TaskChecklist $checklist): JsonResource
    {
        abort_unless($request->user()?->can('edit_tasks'), 403);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:200'],
            'position' => ['sometimes', 'numeric'],
        ]);
        $checklist->update($data);

        return JsonResource::make($checklist->fresh('items'));
    }

    public function destroy(Request $request, TaskChecklist $checklist)
    {
        abort_unless($request->user()?->can('edit_tasks'), 403);
        $checklist->delete();

        return response()->noContent();
    }

    public function storeItem(Request $request, TaskChecklist $checklist): JsonResource
    {
        abort_unless($request->user()?->can('edit_tasks'), 403);

        $data = $request->validate(['text' => ['required', 'string', 'max:1000']]);
        $position = ((float) $checklist->items()->max('position')) + 1000;

        $item = TaskChecklistItem::create([
            'checklist_id' => $checklist->id,
            'text' => $data['text'],
            'position' => $position,
        ]);

        return JsonResource::make($item);
    }

    /**
     * Toggle or edit a checklist item. When is_done flips, we stamp the
     * toggling user + timestamp AND record an activity log entry so the
     * task timeline shows what got ticked off and when.
     */
    public function updateItem(Request $request, TaskChecklistItem $item, TaskActivityService $activity): JsonResource
    {
        abort_unless($request->user()?->can('edit_tasks'), 403);

        $data = $request->validate([
            'text' => ['sometimes', 'string', 'max:1000'],
            'is_done' => ['sometimes', 'boolean'],
            'position' => ['sometimes', 'numeric'],
        ]);

        $didToggle = false;
        if (array_key_exists('is_done', $data)) {
            if ($data['is_done'] && ! $item->is_done) {
                $data['completed_by_id'] = $request->user()->id;
                $data['completed_at'] = now();
                $didToggle = true;
            } elseif (! $data['is_done'] && $item->is_done) {
                $data['completed_by_id'] = null;
                $data['completed_at'] = null;
                $didToggle = true;
            }
        }

        $item->update($data);

        if ($didToggle) {
            $task = $item->checklist?->task;
            if ($task) {
                $activity->checklistItemToggled(
                    task: $task,
                    userId: (int) $request->user()->id,
                    itemId: $item->id,
                    itemText: $item->text,
                    done: (bool) $data['is_done'],
                );
            }
        }

        return JsonResource::make($item->fresh());
    }

    public function destroyItem(Request $request, TaskChecklistItem $item)
    {
        abort_unless($request->user()?->can('edit_tasks'), 403);
        $item->delete();

        return response()->noContent();
    }
}
