<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Enums;

enum AutomationTrigger: string
{
    case TaskCreated = 'task_created';
    case TaskMoved = 'task_moved';
    case TaskCompleted = 'task_completed';
    case TaskAssigned = 'task_assigned';

    public function label(): string
    {
        return match ($this) {
            self::TaskCreated => 'When a task is created',
            self::TaskMoved => 'When a task is moved',
            self::TaskCompleted => 'When a task is completed',
            self::TaskAssigned => 'When a task is assigned',
        };
    }
}
