<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Enums;

enum EngagementStatus: string
{
    case Planning = 'planning';
    case InProgress = 'in_progress';
    case Review = 'review';
    case Completed = 'completed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Planning => 'Planning',
            self::InProgress => 'In Progress',
            self::Review => 'Review',
            self::Completed => 'Completed',
            self::Archived => 'Archived',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::Planning => 'تخطيط',
            self::InProgress => 'قيد التنفيذ',
            self::Review => 'مراجعة',
            self::Completed => 'مكتمل',
            self::Archived => 'مؤرشف',
        };
    }
}
