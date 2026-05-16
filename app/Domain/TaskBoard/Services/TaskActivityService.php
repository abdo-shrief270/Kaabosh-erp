<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Services;

use App\Domain\TaskBoard\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Activitylog\Models\Activity;

/**
 * Unified activity feed for a task. Wraps spatie/laravel-activitylog so:
 *   - attribute changes (title/priority/etc.) get logged automatically via
 *     the LogsActivity trait on Task;
 *   - non-attribute events (comment posted, attachment added, assignee
 *     synced, task moved across columns) get recorded by helper methods
 *     here with a stable `event` key for the UI to render.
 *
 * All entries share `subject = Task` so the per-task timeline is a single
 * query, plus an `event` property in `properties` for filtering.
 */
class TaskActivityService
{
    public function comment(Task $task, int $userId, int $commentId, string $bodyPreview): void
    {
        activity()
            ->causedBy($userId)
            ->performedOn($task)
            ->withProperties(['event' => 'comment_added', 'comment_id' => $commentId, 'preview' => $bodyPreview])
            ->log('comment_added');
    }

    /**
     * @param  string  $action  one of: added | removed
     */
    public function attachment(Task $task, int $userId, int $attachmentId, string $filename, string $action = 'added'): void
    {
        activity()
            ->causedBy($userId)
            ->performedOn($task)
            ->withProperties([
                'event' => "attachment_$action",
                'attachment_id' => $attachmentId,
                'filename' => $filename,
            ])
            ->log("attachment_$action");
    }

    /**
     * @param  int[]  $added
     * @param  int[]  $removed
     */
    public function assigneesChanged(Task $task, int $userId, array $added, array $removed): void
    {
        if (empty($added) && empty($removed)) {
            return;
        }
        activity()
            ->causedBy($userId)
            ->performedOn($task)
            ->withProperties([
                'event' => 'assignees_changed',
                'added' => array_values($added),
                'removed' => array_values($removed),
            ])
            ->log('assignees_changed');
    }

    public function moved(Task $task, int $userId, int $fromColumnId, int $toColumnId): void
    {
        if ($fromColumnId === $toColumnId) {
            return;
        }
        activity()
            ->causedBy($userId)
            ->performedOn($task)
            ->withProperties([
                'event' => 'moved',
                'from_column_id' => $fromColumnId,
                'to_column_id' => $toColumnId,
            ])
            ->log('moved');
    }

    public function checklistItemToggled(Task $task, int $userId, int $itemId, string $itemText, bool $done): void
    {
        activity()
            ->causedBy($userId)
            ->performedOn($task)
            ->withProperties([
                'event' => $done ? 'checklist_item_completed' : 'checklist_item_reopened',
                'item_id' => $itemId,
                'text' => $itemText,
            ])
            ->log($done ? 'checklist_item_completed' : 'checklist_item_reopened');
    }

    public function watch(Task $task, int $userId, bool $watching): void
    {
        activity()
            ->causedBy($userId)
            ->performedOn($task)
            ->withProperties(['event' => $watching ? 'watch_added' : 'watch_removed'])
            ->log($watching ? 'watch_added' : 'watch_removed');
    }

    public function feed(Task $task, int $perPage = 50): LengthAwarePaginator
    {
        return Activity::query()
            ->where('subject_type', (new Task)->getMorphClass())
            ->where('subject_id', $task->id)
            ->with('causer:id,name,email')
            ->latest()
            ->paginate($perPage);
    }
}
