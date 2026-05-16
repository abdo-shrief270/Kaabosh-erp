<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Enums;

enum LeaveStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::Pending => 'قيد الانتظار',
            self::Approved => 'موافق عليه',
            self::Rejected => 'مرفوض',
            self::Cancelled => 'ملغى',
        };
    }

    public function canApprove(): bool
    {
        return $this === self::Pending;
    }

    public function canReject(): bool
    {
        return $this === self::Pending;
    }

    public function canCancel(): bool
    {
        return in_array($this, [self::Pending, self::Approved], true);
    }
}
