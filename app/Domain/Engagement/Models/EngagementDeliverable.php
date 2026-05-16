<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('engagement_deliverables')]
#[Fillable([
    'engagement_id',
    'title_ar',
    'title_en',
    'due_date',
    'is_completed',
    'completed_at',
    'completed_by',
])]
class EngagementDeliverable extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'is_completed' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    /** @var array<string, mixed> */
    protected $attributes = [
        'is_completed' => false,
    ];

    // ──────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────

    public function engagement(): BelongsTo
    {
        return $this->belongsTo(Engagement::class);
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
