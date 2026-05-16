<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Models;

use App\Domain\Document\Models\Document;
use App\Domain\Engagement\Enums\WorkingPaperStatus;
use App\Domain\Shared\Traits\BelongsToTenant;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Table('working_papers')]
#[Fillable([
    'tenant_id',
    'engagement_id',
    'section',
    'reference_code',
    'title_ar',
    'title_en',
    'description',
    'status',
    'assigned_to',
    'reviewed_by',
    'reviewed_at',
    'document_id',
    'notes',
    'sort_order',
])]
class WorkingPaper extends Model
{
    use BelongsToTenant;
    use LogsActivity;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => WorkingPaperStatus::class,
            'reviewed_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'not_started',
        'sort_order' => 0,
    ];

    // ──────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function engagement(): BelongsTo
    {
        return $this->belongsTo(Engagement::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    // ──────────────────────────────────────
    // Activity Log
    // ──────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title_ar', 'status', 'section', 'assigned_to', 'reviewed_by'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
