<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Services;

use App\Domain\TaskBoard\Models\Board;
use App\Domain\TaskBoard\Models\BoardColumn;
use App\Domain\TaskBoard\Models\TaskType;
use Illuminate\Support\Str;

/**
 * Bootstraps a fresh company with task-board defaults: built-in task types
 * (Task, Bug, Story, Subtask, Epic) and a starter board with three columns
 * (To Do / In Progress / Done). Called once on company onboarding so new
 * accounts open the board immediately to a usable surface.
 */
class TaskBoardSeederService
{
    private const DEFAULT_TYPES = [
        ['name' => 'Task',    'slug' => 'task',    'icon' => 'i-lucide-circle-check', 'color' => '#3b82f6'],
        ['name' => 'Bug',     'slug' => 'bug',     'icon' => 'i-lucide-bug',          'color' => '#ef4444'],
        ['name' => 'Story',   'slug' => 'story',   'icon' => 'i-lucide-bookmark',     'color' => '#10b981'],
        ['name' => 'Subtask', 'slug' => 'subtask', 'icon' => 'i-lucide-corner-down-right', 'color' => '#94a3b8', 'is_subtask' => true],
        ['name' => 'Epic',    'slug' => 'epic',    'icon' => 'i-lucide-zap',          'color' => '#8b5cf6', 'is_epic' => true],
    ];

    public function seedTaskTypesFor(int $tenantId): void
    {
        foreach (self::DEFAULT_TYPES as $type) {
            TaskType::withoutGlobalScopes()->firstOrCreate(
                ['tenant_id' => $tenantId, 'slug' => $type['slug']],
                array_merge($type, [
                    'tenant_id' => $tenantId,
                    'is_system' => true,
                    'is_subtask' => $type['is_subtask'] ?? false,
                    'is_epic' => $type['is_epic'] ?? false,
                ]),
            );
        }
    }

    public function seedStarterBoardFor(int $tenantId, ?int $createdBy = null): Board
    {
        $this->seedTaskTypesFor($tenantId);

        /** @var Board $board */
        $board = Board::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'name' => 'My Board',
            'slug' => 'my-board-'.Str::random(6),
            'description' => 'Default board — rename or archive as needed.',
            'color' => '#6366f1',
            'icon' => 'i-lucide-layout-kanban',
            'visibility' => 'company',
            'is_default' => true,
            'key' => 'TASK',
            'created_by' => $createdBy,
        ]);

        $columns = [
            ['name' => 'To Do',       'position' => 1000, 'is_initial' => true,  'color' => '#94a3b8'],
            ['name' => 'In Progress', 'position' => 2000, 'color' => '#3b82f6'],
            ['name' => 'Done',        'position' => 3000, 'is_done' => true,    'color' => '#10b981'],
        ];

        foreach ($columns as $col) {
            BoardColumn::withoutGlobalScopes()->create(array_merge($col, [
                'tenant_id' => $tenantId,
                'board_id' => $board->id,
            ]));
        }

        return $board;
    }
}
