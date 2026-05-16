<?php

declare(strict_types=1);

namespace App\Domain\TimeTracking\Models;

use App\Domain\Client\Models\Client;
use App\Domain\Shared\Traits\BelongsToTenant;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\TimeTracking\Enums\TimesheetStatus;
use App\Models\User;
use Database\Factories\TimesheetEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Table('timesheet_entries')]
#[Fillable([
    'tenant_id',
    'user_id',
    'client_id',
    'date',
    'task_description',
    'hours',
    'is_billable',
    'status',
    'approved_by',
    'approved_at',
    'hourly_rate',
    'notes',
    'invoice_id',
])]
class TimesheetEntry extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'draft',
        'is_billable' => true,
        'hours' => 0,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'hours' => 'decimal:2',
            'is_billable' => 'boolean',
            'status' => TimesheetStatus::class,
            'approved_at' => 'datetime',
            'hourly_rate' => 'decimal:2',
        ];
    }

    // ──────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ──────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────

    public function scopeOfStatus(Builder $query, TimesheetStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForClient(Builder $query, int $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeDateRange(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('date', [$from, $to]);
    }

    public function scopeBillable(Builder $query): Builder
    {
        return $query->where('is_billable', true);
    }

    public function scopeUnbilled(Builder $query): Builder
    {
        return $query->where('is_billable', true)->whereNull('invoice_id');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', TimesheetStatus::Approved);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term): void {
            $q->where('task_description', 'ilike', "%{$term}%")
                ->orWhere('notes', 'ilike', "%{$term}%");
        });
    }

    // ──────────────────────────────────────
    // Activity Log
    // ──────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['user_id', 'status', 'hours', 'is_billable'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected static function newFactory(): TimesheetEntryFactory
    {
        return TimesheetEntryFactory::new();
    }
}
