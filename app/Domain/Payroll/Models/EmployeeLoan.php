<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Models;

use App\Domain\Payroll\Enums\LoanStatus;
use App\Domain\Payroll\Enums\LoanType;
use App\Domain\Shared\Traits\BelongsToTenant;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Table('employee_loans')]
#[Fillable([
    'tenant_id',
    'employee_id',
    'loan_type',
    'status',
    'amount',
    'installment_amount',
    'total_installments',
    'paid_installments',
    'remaining_balance',
    'start_date',
    'end_date',
    'approved_by',
    'approved_at',
    'notes',
])]
class EmployeeLoan extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'loan_type' => LoanType::class,
            'status' => LoanStatus::class,
            'amount' => 'decimal:2',
            'installment_amount' => 'decimal:2',
            'total_installments' => 'integer',
            'paid_installments' => 'integer',
            'start_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'active',
        'paid_installments' => 0,
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

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ──────────────────────────────────────
    // Helper Methods
    // ──────────────────────────────────────

    public function remainingInstallments(): int
    {
        return $this->total_installments - $this->paid_installments;
    }

    public function isActive(): bool
    {
        return $this->status === LoanStatus::Active;
    }

    /**
     * Apply one scheduled installment: reduce remaining_balance by
     * installment_amount, bump paid_installments, and auto-complete the loan
     * when the balance hits zero.
     */
    public function recordInstallment(): void
    {
        $new = bcsub((string) $this->remaining_balance, (string) $this->installment_amount, 2);
        $this->remaining_balance = bccomp($new, '0', 2) <= 0 ? '0.00' : $new;
        $this->paid_installments = ($this->paid_installments ?? 0) + 1;

        if (bccomp((string) $this->remaining_balance, '0', 2) === 0) {
            $this->status = LoanStatus::Completed;
        }

        $this->save();
    }

    // ──────────────────────────────────────
    // Activity Log
    // ──────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'amount', 'paid_installments', 'total_installments'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
