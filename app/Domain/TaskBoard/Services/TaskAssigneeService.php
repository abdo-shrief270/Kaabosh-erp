<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Services;

use App\Domain\TaskBoard\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Encapsulates assignee + watcher pivot mutations so controllers stay slim
 * and so notification dispatch on add/remove flows through one place.
 */
class TaskAssigneeService
{
    /**
     * Replace the full assignee set for a task. Returns the resulting user
     * ids in canonical order. Primary assignee is preserved if still present
     * in the new set, otherwise falls back to the first id (or null).
     *
     * @param  int[]  $userIds
     */
    public function syncAssignees(Task $task, array $userIds, ?int $actorId = null): array
    {
        $ids = collect($userIds)->map('intval')->unique()->values();

        $this->guardSameTenant($task, $ids->all());

        DB::transaction(function () use ($task, $ids, $actorId) {
            $pivot = $ids->mapWithKeys(fn ($id) => [
                $id => ['assigned_by_id' => $actorId, 'assigned_at' => now()],
            ])->all();

            $task->assignees()->sync($pivot);

            if (! $task->primary_assignee_id || ! $ids->contains($task->primary_assignee_id)) {
                $task->forceFill(['primary_assignee_id' => $ids->first()])->save();
            }
        });

        return $ids->all();
    }

    public function setPrimaryAssignee(Task $task, ?int $userId, ?int $actorId = null): Task
    {
        if ($userId !== null) {
            $this->guardSameTenant($task, [$userId]);

            // Auto-add to the assignee set when promoting an outsider.
            if (! $task->assignees()->where('users.id', $userId)->exists()) {
                $task->assignees()->attach($userId, [
                    'assigned_by_id' => $actorId,
                    'assigned_at' => now(),
                ]);
            }
        }

        $task->forceFill(['primary_assignee_id' => $userId])->save();

        return $task->refresh();
    }

    public function watch(Task $task, int $userId): void
    {
        $this->guardSameTenant($task, [$userId]);
        $task->watchers()->syncWithoutDetaching([$userId]);
    }

    public function unwatch(Task $task, int $userId): void
    {
        $task->watchers()->detach($userId);
    }

    /**
     * @param  int[]  $userIds
     */
    private function guardSameTenant(Task $task, array $userIds): void
    {
        if (empty($userIds)) {
            return;
        }
        $existing = User::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $task->tenant_id)
            ->whereIn('id', $userIds)
            ->pluck('id')
            ->all();

        if (count($existing) !== count(array_unique($userIds))) {
            throw new InvalidArgumentException('All assignees must belong to the same company.');
        }
    }
}
