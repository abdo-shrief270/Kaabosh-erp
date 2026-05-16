<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Enums;

enum VersionStatus: string
{
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Released = 'released';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planned',
            self::InProgress => 'In Progress',
            self::Released => 'Released',
            self::Archived => 'Archived',
        };
    }
}
