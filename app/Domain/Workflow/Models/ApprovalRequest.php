<?php

declare(strict_types=1);

namespace App\Domain\Workflow\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use App\Domain\Workflow\Enums\ApprovalStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('approval_requests')]
#[Fillable([
    'tenant_id',
    'workflow_id',
    'entity_type',
    'entity_id',
    'current_step',
    'status',
    'requested_by',
])]
class ApprovalRequest extends Model
{
    use BelongsToTenant;

    /** @var array<string, mixed> */
    protected $attributes = [
        'current_step' => 1,
        'status' => 'pending',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ApprovalStatus::class,
            'current_step' => 'integer',
            'entity_id' => 'integer',
        ];
    }

    // ──────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'workflow_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(ApprovalAction::class, 'approval_request_id')->orderBy('step_order');
    }

    // ──────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────

    public function isPending(): bool
    {
        return in_array($this->status, [ApprovalStatus::Pending, ApprovalStatus::InProgress], true);
    }
}
