<?php

declare(strict_types=1);

namespace App\Domain\TimeTracking\Enums;

enum TimesheetStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::Draft => 'مسودة',
            self::Submitted => 'مقدمة',
            self::Approved => 'معتمدة',
            self::Rejected => 'مرفوضة',
        };
    }

    public function canEdit(): bool
    {
        return in_array($this, [self::Draft, self::Rejected], true);
    }

    public function canSubmit(): bool
    {
        return in_array($this, [self::Draft, self::Rejected], true);
    }

    public function canApprove(): bool
    {
        return $this === self::Submitted;
    }

    public function canReject(): bool
    {
        return $this === self::Submitted;
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Submitted => 'blue',
            self::Approved => 'green',
            self::Rejected => 'red',
        };
    }
}
