<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Models;

use Database\Factories\PayrollItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Table('payroll_items')]
#[Fillable([
    'payroll_run_id',
    'employee_id',
    'base_salary',
    'allowances',
    'overtime_hours',
    'overtime_amount',
    'gross_salary',
    'social_insurance_employee',
    'social_insurance_employer',
    'taxable_income',
    'income_tax',
    'other_deductions',
    'net_salary',
    'notes',
])]
class PayrollItem extends Model
{
    use HasFactory;
    use LogsActivity;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'base_salary' => 'decimal:2',
            'allowances' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'overtime_amount' => 'decimal:2',
            'gross_salary' => 'decimal:2',
            'social_insurance_employee' => 'decimal:2',
            'social_insurance_employer' => 'decimal:2',
            'taxable_income' => 'decimal:2',
            'income_tax' => 'decimal:2',
            'other_deductions' => 'decimal:2',
            'net_salary' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['base_salary', 'gross_salary', 'net_salary'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Payroll item {$eventName}");
    }

    // ──────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    protected static function newFactory(): PayrollItemFactory
    {
        return PayrollItemFactory::new();
    }
}
