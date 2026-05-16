<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Models\TaskRecurrence;
use App\Domain\TaskBoard\Services\RecurrenceSpawner;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskRecurrenceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = TaskRecurrence::query()->with(['column', 'type', 'defaultAssignee:id,name,email']);
        if ($boardId = $request->integer('board_id')) {
            $query->where('board_id', $boardId);
        }
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return JsonResource::collection($query->orderBy('next_spawn_at')->paginate(50));
    }

    public function store(Request $request, RecurrenceSpawner $spawner): JsonResource
    {
        abort_unless($request->user()?->can('manage_recurrences'), 403);

        $tenantId = (int) app('tenant.id');

        $data = $request->validate([
            'board_id' => ['required', 'integer', "exists:boards,id,tenant_id,$tenantId"],
            'board_column_id' => ['required', 'integer', "exists:board_columns,id,tenant_id,$tenantId"],
            'task_type_id' => ['required', 'integer', "exists:task_types,id,tenant_id,$tenantId"],
            'title' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'priority' => ['nullable', 'in:low,medium,high,critical'],
            'default_assignee_id' => ['nullable', 'integer', "exists:users,id,tenant_id,$tenantId"],
            'default_tag_ids' => ['nullable', 'array'],
            'default_tag_ids.*' => ['integer', "exists:tags,id,tenant_id,$tenantId"],
            'frequency' => ['required', 'in:daily,weekly,monthly,yearly,cron'],
            'interval' => ['nullable', 'integer', 'min:1', 'max:365'],
            'byday' => ['nullable', 'array'],
            'cron_expression' => ['required_if:frequency,cron', 'nullable', 'string', 'max:80'],
            'timezone' => ['nullable', 'string', 'max:60', 'timezone'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'max_occurrences' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ]);

        $data['interval'] ??= 1;
        $data['priority'] ??= 'medium';
        $data['timezone'] ??= 'Africa/Cairo';
        $data['created_by'] = $request->user()->id;
        $data['is_active'] = true;
        $data['spawned_count'] = 0;

        /** @var TaskRecurrence $r */
        $r = TaskRecurrence::create($data);

        // Compute next_spawn_at = starts_at, or first occurrence after now if
        // starts_at is in the past (no retroactive spawning).
        $now = CarbonImmutable::now();
        $startsAt = CarbonImmutable::parse($r->starts_at);
        $r->next_spawn_at = $startsAt->isFuture() ? $startsAt : $spawner->nextOccurrence($r, $now);
        $r->save();

        return JsonResource::make($r);
    }

    public function update(Request $request, TaskRecurrence $taskRecurrence): JsonResource
    {
        abort_unless($request->user()?->can('manage_recurrences'), 403);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'priority' => ['sometimes', 'in:low,medium,high,critical'],
            'default_assignee_id' => ['nullable', 'integer'],
            'default_tag_ids' => ['nullable', 'array'],
            'frequency' => ['sometimes', 'in:daily,weekly,monthly,yearly,cron'],
            'interval' => ['sometimes', 'integer', 'min:1', 'max:365'],
            'byday' => ['nullable', 'array'],
            'cron_expression' => ['nullable', 'string', 'max:80'],
            'ends_at' => ['nullable', 'date'],
            'max_occurrences' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $taskRecurrence->update($data);

        return JsonResource::make($taskRecurrence->fresh());
    }

    public function destroy(Request $request, TaskRecurrence $taskRecurrence)
    {
        abort_unless($request->user()?->can('manage_recurrences'), 403);
        $taskRecurrence->delete();

        return response()->noContent();
    }
}
