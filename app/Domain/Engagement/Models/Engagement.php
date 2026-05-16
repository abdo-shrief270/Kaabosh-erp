<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Models;

use App\Domain\Client\Models\Client;
use App\Domain\Engagement\Enums\EngagementStatus;
use App\Domain\Engagement\Enums\EngagementType;
use App\Domain\Engagement\Enums\WorkingPaperStatus;
use App\Domain\Shared\Traits\BelongsToTenant;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Table('engagements')]
#[Fillable([
    'tenant_id',
    'client_id',
    'fiscal_year_id',
    'engagement_type',
    'name_ar',
    'name_en',
    'status',
    'manager_id',
    'partner_id',
    'planned_hours',
    'actual_hours',
    'budget_amount',
    'actual_amount',
    'start_date',
    'end_date',
    'deadline',
    'notes',
    'created_by',
])]
class Engagement extends Model
{
    use BelongsToTenant;
    use LogsActivity;
    use SoftDeletes;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'engagement_type' => EngagementType::class,
            'status' => EngagementStatus::class,
            'planned_hours' => 'decimal:2',
            'actual_hours' => 'decimal:2',
            'budget_amount' => 'decimal:2',
            'actual_amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'deadline' => 'date',
        ];
    }

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'planning',
        'planned_hours' => 0,
        'actual_hours' => 0,
        'budget_amount' => 0,
        'actual_amount' => 0,
    ];

    // ──────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'partner_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function workingPapers(): HasMany
    {
        return $this->hasMany(WorkingPaper::class)->orderBy('sort_order');
    }

    public function deliverables(): HasMany
    {
        return $this->hasMany(EngagementDeliverable::class);
    }

    // ──────────────────────────────────────
    // Methods
    // ──────────────────────────────────────

    /**
     * Calculate progress as percentage of completed working papers.
     */
    public function progress(): float
    {
        $total = $this->workingPapers()->count();

        if ($total === 0) {
            return 0.0;
        }

        $completed = $this->workingPapers()
            ->whereIn('status', [WorkingPaperStatus::Completed->value, WorkingPaperStatus::Reviewed->value])
            ->count();

        return round(($completed / $total) * 100, 2);
    }

    // ──────────────────────────────────────
    // Activity Log
    // ──────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name_ar', 'status', 'engagement_type', 'manager_id', 'partner_id'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
