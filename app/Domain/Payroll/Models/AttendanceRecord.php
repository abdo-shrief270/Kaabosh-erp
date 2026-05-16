<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Models;

use App\Domain\Payroll\Enums\AttendanceStatus;
use App\Domain\Shared\Traits\BelongsToTenant;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('attendance_records')]
#[Fillable([
    'tenant_id',
    'employee_id',
    'date',
    'status',
    'check_in',
    'check_out',
    'hours_worked',
    'overtime_hours',
    'notes',
])]
class AttendanceRecord extends Model
{
    use BelongsToTenant;

    private const STANDARD_HOURS = 8;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'status' => AttendanceStatus::class,
            'hours_worked' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
        ];
    }

    // ──────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    // ──────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────

    /**
     * Compute hours_worked and overtime_hours from check_in/check_out.
     * Anything over STANDARD_HOURS counts as overtime.
     */
    public function calculateHours(): void
    {
        if (! $this->check_in || ! $this->check_out) {
            return;
        }

        $in = $this->parseTime((string) $this->check_in);
        $out = $this->parseTime((string) $this->check_out);
        $diffSeconds = max(0, $out - $in);
        $hours = $diffSeconds / 3600;

        $this->hours_worked = number_format($hours, 2, '.', '');
        $this->overtime_hours = number_format(max(0, $hours - self::STANDARD_HOURS), 2, '.', '');
    }

    private function parseTime(string $value): int
    {
        // Accept "HH:MM", "HH:MM:SS", or full datetime strings.
        $value = trim($value);
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $value, $m)) {
            return ((int) $m[1]) * 3600 + ((int) $m[2]) * 60 + ((int) ($m[3] ?? 0));
        }

        return (int) strtotime($value);
    }
}
