<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('leave_balances')]
#[Fillable([
    'tenant_id',
    'employee_id',
    'leave_type_id',
    'year',
    'entitled_days',
    'used_days',
    'carried_days',
])]
class LeaveBalance extends Model
{
    use BelongsToTenant;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'entitled_days' => 'integer',
            'used_days' => 'integer',
            'carried_days' => 'integer',
        ];
    }

    /** @var array<string, mixed> */
    protected $attributes = [
        'used_days' => 0,
        'carried_days' => 0,
    ];

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

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    // ──────────────────────────────────────
    // Helper Methods
    // ──────────────────────────────────────

    /**
     * Calculate available leave days: entitled + carried - used.
     */
    public function availableDays(): int
    {
        return ($this->entitled_days ?? 0) + ($this->carried_days ?? 0) - ($this->used_days ?? 0);
    }

    /**
     * Record leave taken against this balance and persist.
     */
    public function deductDays(int $days): void
    {
        $this->used_days = ($this->used_days ?? 0) + $days;
        $this->save();
    }
}
