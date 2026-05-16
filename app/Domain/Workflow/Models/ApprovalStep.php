<?php

declare(strict_types=1);

namespace App\Domain\Workflow\Models;

use App\Domain\Workflow\Enums\ApproverType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('approval_steps')]
#[Fillable([
    'workflow_id',
    'step_order',
    'approver_type',
    'approver_id',
    'approval_limit',
    'timeout_hours',
])]
class ApprovalStep extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'approver_type' => ApproverType::class,
            'step_order' => 'integer',
            'approver_id' => 'integer',
            'approval_limit' => 'decimal:2',
            'timeout_hours' => 'integer',
        ];
    }

    // ──────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'workflow_id');
    }
}
