<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Models;

use App\Domain\Payroll\Enums\CalculationType;
use App\Domain\Payroll\Enums\SalaryComponentType;
use App\Domain\Shared\Traits\BelongsToTenant;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Table('salary_components')]
#[Fillable([
    'tenant_id',
    'name',
    'name_ar',
    'type',
    'calculation_type',
    'default_amount',
    'is_active',
    'is_taxable',
    'sort_order',
])]
class SalaryComponent extends Model
{
    use BelongsToTenant;
    use LogsActivity;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => SalaryComponentType::class,
            'calculation_type' => CalculationType::class,
            'default_amount' => 'decimal:2',
            'is_active' => 'boolean',
            'is_taxable' => 'boolean',
        ];
    }

    // ──────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ──────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAllowances(Builder $query): Builder
    {
        return $query->where('type', SalaryComponentType::Allowance);
    }

    public function scopeDeductions(Builder $query): Builder
    {
        return $query->where('type', SalaryComponentType::Deduction);
    }

    public function scopeContributions(Builder $query): Builder
    {
        return $query->where('type', SalaryComponentType::Contribution);
    }

    // ──────────────────────────────────────
    // Activity Log
    // ──────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'type', 'calculation_type', 'default_amount', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
