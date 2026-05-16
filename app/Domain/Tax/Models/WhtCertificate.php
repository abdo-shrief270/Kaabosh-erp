<?php

declare(strict_types=1);

namespace App\Domain\Tax\Models;

use App\Domain\AccountsPayable\Models\Vendor;
use App\Domain\Shared\Traits\BelongsToTenant;
use App\Domain\Tax\Enums\WhtCertificateStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Table('wht_certificates')]
#[Fillable([
    'tenant_id',
    'vendor_id',
    'certificate_number',
    'period_from',
    'period_to',
    'total_taxable_amount',
    'wht_rate',
    'wht_amount',
    'status',
    'issued_at',
    'submitted_at',
    'notes',
    'created_by',
])]
class WhtCertificate extends Model
{
    use BelongsToTenant;
    use LogsActivity;
    use SoftDeletes;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => WhtCertificateStatus::class,
            'period_from' => 'date',
            'period_to' => 'date',
            'total_taxable_amount' => 'decimal:2',
            'wht_rate' => 'decimal:2',
            'wht_amount' => 'decimal:2',
            'issued_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'draft',
    ];

    // ──────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ──────────────────────────────────────
    // Activity Log
    // ──────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['certificate_number', 'status', 'wht_amount', 'wht_rate'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
