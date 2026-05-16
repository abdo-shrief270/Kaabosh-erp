<?php

declare(strict_types=1);

namespace App\Domain\Workflow\Enums;

enum ApprovalStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::InProgress => 'In Progress',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::Pending => 'معلقة',
            self::InProgress => 'قيد المعالجة',
            self::Approved => 'معتمدة',
            self::Rejected => 'مرفوضة',
            self::Cancelled => 'ملغاة',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::InProgress => 'blue',
            self::Approved => 'green',
            self::Rejected => 'red',
            self::Cancelled => 'orange',
        };
    }
}
