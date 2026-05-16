<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('leave_types')]
#[Fillable([
    'tenant_id',
    'name',
    'name_ar',
    'default_days',
    'is_paid',
    'is_active',
    'carry_forward',
    'max_carry_forward_days',
])]
class LeaveType extends Model
{
    use BelongsToTenant;
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'default_days' => 'decimal:2',
            'is_paid' => 'boolean',
            'is_active' => 'boolean',
            'carry_forward' => 'boolean',
            'max_carry_forward_days' => 'decimal:2',
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
}
