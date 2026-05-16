<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Services;

use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskChecklist;
use App\Domain\TaskBoard\Models\TaskChecklistItem;
use App\Domain\TaskBoard\Models\TaskTemplate;
use Illuminate\Support\Facades\DB;

/**
 * Two-way template service: snapshot an existing task into a template, OR
 * spawn a concrete task from one. Spawned tasks copy the title (minus a
 * "{{date}}" token substitution, kept simple — no full mustache), body,
 * priority, estimate, tags, plus an auto-checklist if the template defines
 * one.
 */
class TaskTemplateService
{
    /**
     * Create a template from an existing task. The user picks the name +
     * scope (board-specific or company-wide); everything else snapshots.
     */
    public function createFromTask(Task $task, string $name, ?int $boardScopeId, ?int $createdBy = null): TaskTemplate
    {
        $tagIds = $task->tags()->pluck('tags.id')->all();

        return TaskTemplate::create([
            'tenant_id' => $task->tenant_id,
            'board_id' => $boardScopeId,
            'task_type_id' => $task->task_type_id,
            'created_by' => $createdBy,
            'name' => $name,
            'description' => null,
            'title_template' => $task->title,
            'body_template' => $task->description,
            'priority' => $task->priority?->value ?? 'medium',
            'default_estimate_hours' => $task->estimate_hours,
            'default_tag_ids' => $tagIds,
            'default_checklist' => null,
        ]);
    }

    /**
     * Spawn a concrete task into the given board+column. Bumps use_count
     * for the "most used" sort on the picker.
     *
     * @param  array<string, mixed>  $override  optional fields to override on this spawn
     */
    public function spawn(TaskTemplate $template, int $boardId, int $boardColumnId, array $override = []): Task
    {
        return DB::transaction(function () use ($template, $boardId, $boardColumnId, $override) {
            $title = $this->renderTitle($template->title_template);

            /** @var Task $task */
            $task = Task::create(array_merge([
                'tenant_id' => $template->tenant_id,
                'board_id' => $boardId,
                'board_column_id' => $boardColumnId,
                'task_type_id' => $template->task_type_id,
                'title' => $title,
                'description' => $template->body_template,
                'priority' => $template->priority,
                'estimate_hours' => $template->default_estimate_hours,
            ], $override));

            if (! empty($template->default_tag_ids)) {
                $task->tags()->sync($template->default_tag_ids);
            }

            if (! empty($template->default_checklist)) {
                /** @var TaskChecklist $checklist */
                $checklist = TaskChecklist::create([
                    'task_id' => $task->id,
                    'title' => 'Checklist',
                    'position' => 1000,
                ]);
                foreach ($template->default_checklist as $i => $text) {
                    TaskChecklistItem::create([
                        'checklist_id' => $checklist->id,
                        'text' => (string) $text,
                        'position' => ($i + 1) * 1000,
                    ]);
                }
            }

            $template->forceFill(['use_count' => $template->use_count + 1])->save();

            return $task;
        });
    }

    /**
     * Tiny token engine — replaces {{date}} / {{week}} / {{month}} in the
     * stored title with the current locale-formatted values. Keeps templates
     * useful for recurring shapes ("Daily standup — {{date}}") without
     * needing a real templating language on the client.
     */
    private function renderTitle(string $template): string
    {
        $now = now();
        return strtr($template, [
            '{{date}}' => $now->toDateString(),
            '{{week}}' => $now->isoFormat('GGGG-[W]WW'),
            '{{month}}' => $now->isoFormat('YYYY-MM'),
        ]);
    }
}
