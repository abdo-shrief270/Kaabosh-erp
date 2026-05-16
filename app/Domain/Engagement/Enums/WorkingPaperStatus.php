<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Enums;

enum WorkingPaperStatus: string
{
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Reviewed = 'reviewed';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Not Started',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::Reviewed => 'Reviewed',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::NotStarted => 'لم يبدأ',
            self::InProgress => 'قيد التنفيذ',
            self::Completed => 'مكتمل',
            self::Reviewed => 'تمت المراجعة',
        };
    }
}
