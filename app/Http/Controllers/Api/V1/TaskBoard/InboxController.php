<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskComment;
use App\Domain\TaskBoard\Models\TaskReminder;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaskBoard\TaskResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Cross-board "inbox" for the current user. Aggregates three signals
 * into one feed:
 *
 *   - mentioned    — comments that named the user via @-mention
 *   - assigned     — tasks the user is primary or co-assignee on
 *   - watching     — tasks the user is watching but not assigned to
 *   - reminders    — upcoming reminders the user set on themselves
 *
 * Each item carries its `task` so the SPA can render rich rows without a
 * second round-trip. The filter param `kind=` narrows the feed.
 */
class InboxController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $kind = $request->string('kind')->toString() ?: 'all';

        $items = collect();

        if ($kind === 'all' || $kind === 'mentioned') {
            $items = $items->concat($this->mentions($userId));
        }
        if ($kind === 'all' || $kind === 'assigned') {
            $items = $items->concat($this->assignments($userId));
        }
        if ($kind === 'all' || $kind === 'watching') {
            $items = $items->concat($this->watching($userId));
        }
        if ($kind === 'all' || $kind === 'reminders') {
            $items = $items->concat($this->reminders($userId));
        }

        // Sort by timestamp desc, dedupe by (kind,task_id,reference id).
        $items = $items
            ->unique(fn ($i) => $i['kind'].':'.$i['task']['id'].':'.($i['reference_id'] ?? ''))
            ->sortByDesc('at')
            ->values()
            ->take(100);

        return response()->json(['data' => $items]);
    }

    /** @return array<int, array<string, mixed>> */
    private function mentions(int $userId): array
    {
        $comments = TaskComment::query()
            ->whereJsonContains('mentions', $userId)
            ->orWhereJsonContains('mentions', (string) $userId)
            ->latest('id')
            ->limit(80)
            ->with(['task.type', 'task.column', 'task.primaryAssignee', 'user:id,name'])
            ->get()
            ->filter(fn ($c) => $c->task && $c->task->tenant_id === app('tenant.id'));

        return $comments->map(fn ($c) => [
            'kind' => 'mentioned',
            'reference_id' => $c->id,
            'at' => $c->created_at?->toIso8601String(),
            'preview' => mb_substr(strip_tags((string) $c->body), 0, 160),
            'actor' => $c->user ? ['id' => $c->user->id, 'name' => $c->user->name] : null,
            'task' => TaskResource::make($c->task)->resolve(),
        ])->values()->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function assignments(int $userId): array
    {
        $tasks = Task::query()
            ->where(function ($q) use ($userId) {
                $q->where('primary_assignee_id', $userId)
                    ->orWhereHas('assignees', fn ($a) => $a->where('users.id', $userId));
            })
            ->whereNull('archived_at')
            ->whereNull('completed_at')
            ->with(['type', 'column', 'primaryAssignee'])
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        return $tasks->map(fn ($t) => [
            'kind' => 'assigned',
            'reference_id' => null,
            'at' => $t->updated_at?->toIso8601String(),
            'preview' => null,
            'actor' => null,
            'task' => TaskResource::make($t)->resolve(),
        ])->values()->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function watching(int $userId): array
    {
        // Watching but NOT assigned (avoid duplicating the "assigned" pile).
        $tasks = Task::query()
            ->whereHas('watchers', fn ($w) => $w->where('users.id', $userId))
            ->where(function ($q) use ($userId) {
                $q->where('primary_assignee_id', '!=', $userId)
                    ->orWhereNull('primary_assignee_id');
            })
            ->whereDoesntHave('assignees', fn ($a) => $a->where('users.id', $userId))
            ->whereNull('archived_at')
            ->with(['type', 'column', 'primaryAssignee'])
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        return $tasks->map(fn ($t) => [
            'kind' => 'watching',
            'reference_id' => null,
            'at' => $t->updated_at?->toIso8601String(),
            'preview' => null,
            'actor' => null,
            'task' => TaskResource::make($t)->resolve(),
        ])->values()->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function reminders(int $userId): array
    {
        $reminders = TaskReminder::query()
            ->where('user_id', $userId)
            ->whereNull('sent_at')
            ->orderBy('remind_at')
            ->limit(50)
            ->with(['task.type', 'task.column', 'task.primaryAssignee'])
            ->get()
            ->filter(fn ($r) => $r->task && $r->task->tenant_id === app('tenant.id'));

        return $reminders->map(fn ($r) => [
            'kind' => 'reminders',
            'reference_id' => $r->id,
            'at' => $r->remind_at?->toIso8601String(),
            'preview' => $r->note,
            'actor' => null,
            'task' => TaskResource::make($r->task)->resolve(),
        ])->values()->all();
    }
}
