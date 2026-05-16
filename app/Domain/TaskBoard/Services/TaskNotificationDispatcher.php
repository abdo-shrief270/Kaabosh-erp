<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Services;

use App\Domain\Notification\Enums\NotificationChannel;
use App\Domain\Notification\Enums\NotificationType;
use App\Domain\Notification\Services\NotificationService;
use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskComment;
use Illuminate\Support\Str;

/**
 * Translates task-board events into per-user notifications. The interesting
 * set for any task = {primary_assignee} ∪ assignees ∪ watchers ∪ mentioned.
 * The actor is always deduped out so people never get notified about their
 * own actions.
 *
 * Channel defaults to InApp; email is out of scope until the mailer is wired.
 */
class TaskNotificationDispatcher
{
    public function __construct(private readonly NotificationService $notifications) {}

    /**
     * Notify everyone interested in this task that a new comment landed.
     * Mentioned users get a richer "you were mentioned" notification instead
     * of the generic comment one.
     */
    public function commentPosted(Task $task, TaskComment $comment): void
    {
        $actorId = (int) $comment->user_id;
        $mentioned = collect($comment->mentions ?? [])->map(fn ($id) => (int) $id);
        $interested = $this->interestedUsers($task)->reject(fn ($id) => $id === $actorId);

        $preview = Str::limit(strip_tags($comment->body), 120);
        $actionUrl = $this->taskUrl($task);

        // Mentions first — they take precedence over the generic comment.
        foreach ($mentioned->reject(fn ($id) => $id === $actorId)->unique() as $userId) {
            $this->notifications->send(
                userId: $userId,
                type: NotificationType::TaskMentioned,
                titleAr: 'تم ذكرك في '.$task->reference,
                titleEn: 'You were mentioned in '.$task->reference,
                bodyAr: $preview,
                bodyEn: $preview,
                actionUrl: $actionUrl,
                data: ['task_id' => $task->id, 'comment_id' => $comment->id],
                channel: NotificationChannel::InApp,
            );
        }

        // Other interested users (not mentioned) get the generic comment notification.
        $otherTargets = $interested->reject(fn ($id) => $mentioned->contains($id))->unique();
        foreach ($otherTargets as $userId) {
            $this->notifications->send(
                userId: $userId,
                type: NotificationType::TaskCommented,
                titleAr: 'تعليق جديد على '.$task->reference,
                titleEn: 'New comment on '.$task->reference,
                bodyAr: $preview,
                bodyEn: $preview,
                actionUrl: $actionUrl,
                data: ['task_id' => $task->id, 'comment_id' => $comment->id],
                channel: NotificationChannel::InApp,
            );
        }
    }

    /**
     * Notify newly-added assignees that the task is now theirs.
     *
     * @param  int[]  $newlyAddedUserIds
     */
    public function assigneesAdded(Task $task, array $newlyAddedUserIds, ?int $actorId = null): void
    {
        foreach (array_unique($newlyAddedUserIds) as $userId) {
            if ($userId === $actorId) {
                continue; // don't notify the person who assigned themselves
            }
            $this->notifications->send(
                userId: (int) $userId,
                type: NotificationType::TaskAssigned,
                titleAr: 'تم تعيين مهمة لك: '.$task->reference,
                titleEn: 'Assigned to you: '.$task->reference,
                bodyAr: $task->title,
                bodyEn: $task->title,
                actionUrl: $this->taskUrl($task),
                data: ['task_id' => $task->id],
                channel: NotificationChannel::InApp,
            );
        }
    }

    /**
     * When a task lands in a "done" column, the reporter gets a soft heads-up.
     */
    public function taskMoved(Task $task, int $fromColumnId, int $toColumnId, ?int $actorId = null): void
    {
        if (! $task->reporter_id || $task->reporter_id === $actorId) {
            return;
        }
        // Only notify on entering done — otherwise this gets noisy.
        if (! $task->completed_at) {
            return;
        }

        $this->notifications->send(
            userId: (int) $task->reporter_id,
            type: NotificationType::TaskMoved,
            titleAr: 'تم إكمال '.$task->reference,
            titleEn: $task->reference.' was completed',
            bodyAr: $task->title,
            bodyEn: $task->title,
            actionUrl: $this->taskUrl($task),
            data: ['task_id' => $task->id, 'from_column_id' => $fromColumnId, 'to_column_id' => $toColumnId],
            channel: NotificationChannel::InApp,
        );
    }

    /**
     * Union of all users who care about a task — primary assignee + the
     * assignees pivot + the watchers pivot, minus anyone who muted the
     * task. Deduped, integers, no actor filtering (callers do that based
     * on context).
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function interestedUsers(Task $task): \Illuminate\Support\Collection
    {
        $assignees = $task->assignees()->pluck('users.id');
        $watchers = $task->watchers()->pluck('users.id');
        $set = $assignees->concat($watchers);
        if ($task->primary_assignee_id) {
            $set = $set->push($task->primary_assignee_id);
        }
        $muted = $task->mutedBy()->pluck('users.id')->map(fn ($v) => (int) $v)->all();

        return $set->map(fn ($v) => (int) $v)
            ->unique()
            ->reject(fn ($id) => in_array($id, $muted, true))
            ->values();
    }

    private function taskUrl(Task $task): string
    {
        $base = rtrim((string) config('app.frontend_url', config('app.url')), '/');

        return $base.'/tasks/'.$task->id;
    }
}
