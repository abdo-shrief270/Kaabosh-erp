<?php

declare(strict_types=1);

namespace App\Http\Resources\TaskBoard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'board_id' => $this->board_id,
            'board_column_id' => $this->board_column_id,
            'task_type_id' => $this->task_type_id,
            'parent_task_id' => $this->parent_task_id,
            'reference' => $this->reference,
            'number' => $this->number,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority?->value,
            'reporter_id' => $this->reporter_id,
            'primary_assignee_id' => $this->primary_assignee_id,
            'start_date' => $this->start_date,
            'due_date' => $this->due_date,
            'estimate_hours' => $this->estimate_hours,
            'logged_hours' => $this->logged_hours,
            'progress' => $this->progress,
            'position' => $this->position,
            'completed_at' => $this->completed_at,
            'archived_at' => $this->archived_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // Share link state — exposed on the detail view so ShareTaskButton
            // knows whether to render the create form or the existing link.
            'share_token' => $this->share_token,
            'shared_until' => $this->shared_until,
            'share_url' => $this->share_token
                ? rtrim((string) config('app.frontend_url', config('app.url')), '/').'/shared/task/'.$this->share_token
                : null,
            'type' => TaskTypeResource::make($this->whenLoaded('type')),
            'column' => BoardColumnResource::make($this->whenLoaded('column')),
            'primary_assignee' => $this->whenLoaded('primaryAssignee', fn () => [
                'id' => $this->primaryAssignee?->id,
                'name' => $this->primaryAssignee?->name,
                'email' => $this->primaryAssignee?->email,
            ]),
            'assignees' => $this->whenLoaded('assignees', fn () => $this->assignees->map(fn ($u) => [
                'id' => $u->id, 'name' => $u->name, 'email' => $u->email,
            ])),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'versions' => VersionResource::collection($this->whenLoaded('versions')),
            'subtasks_count' => $this->whenCounted('subtasks'),
            'comments_count' => $this->whenCounted('comments'),
            'attachments_count' => $this->whenCounted('attachments'),
            // Per-user flags from the EXISTS sub-selects in TaskController::index
            // (and explicit lookups in show). Boolean for the SPA — non-null
            // → user has the flag set.
            'is_starred' => (bool) ($this->is_starred ?? false),
            'is_muted' => (bool) ($this->is_muted ?? false),
            // Custom field values keyed by definition id. Always emitted as
            // an object (possibly empty) so the SPA can assume the shape.
            'custom_fields' => $this->whenLoaded('customFieldValues', function () {
                return $this->customFieldValues->mapWithKeys(
                    fn ($v) => [(string) $v->custom_field_id => $v->value],
                );
            }, (object) []),
            // Aliased withCount on the HasManyThrough relation; only present
            // on the list/Kanban payload where withCount is applied.
            'checklist_total' => $this->checklist_total ?? null,
            'checklist_done' => $this->checklist_done ?? null,
            // Active-sprint chip — set by the correlated sub-select in
            // TaskController::index, null on the detail view.
            'current_sprint' => ($this->resource->getAttributes()['current_sprint_id'] ?? null)
                ? ['id' => (int) $this->current_sprint_id, 'name' => (string) ($this->resource->getAttributes()['current_sprint_name'] ?? '')]
                : null,
            // Recursive — each subtask is itself a TaskResource so the full
            // tree is exposed in one payload. Backed by the deep eager-load
            // in TaskController::show; on list/Kanban payloads `subtasks`
            // isn't loaded so this returns an empty array.
            'subtasks' => TaskResource::collection($this->whenLoaded('subtasks')),
            'reactions' => $this->whenLoaded('reactions', function () use ($request) {
                $currentId = (int) ($request->user()?->id ?? 0);
                $byEmoji = [];
                foreach ($this->reactions as $r) {
                    $byEmoji[$r->emoji] ??= ['emoji' => $r->emoji, 'count' => 0, 'mine' => false, 'user_ids' => []];
                    $byEmoji[$r->emoji]['count']++;
                    $byEmoji[$r->emoji]['user_ids'][] = (int) $r->user_id;
                    if ((int) $r->user_id === $currentId) {
                        $byEmoji[$r->emoji]['mine'] = true;
                    }
                }
                return array_values($byEmoji);
            }),
        ];
    }
}
