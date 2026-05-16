<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Enums;

enum TaskPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
            self::Critical => 'Critical',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::Low => 'منخفض',
            self::Medium => 'متوسط',
            self::High => 'عالي',
            self::Critical => 'حرج',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low => '#94a3b8',
            self::Medium => '#3b82f6',
            self::High => '#f59e0b',
            self::Critical => '#ef4444',
        };
    }

    public function weight(): int
    {
        return match ($this) {
            self::Low => 1,
            self::Medium => 2,
            self::High => 3,
            self::Critical => 4,
        };
    }
}
