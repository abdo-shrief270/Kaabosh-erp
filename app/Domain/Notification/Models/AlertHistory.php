<?php

declare(strict_types=1);

namespace App\Domain\Notification\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('alert_history')]
#[Fillable([
    'tenant_id',
    'alert_rule_id',
    'triggered_at',
    'metric_value',
    'threshold_value',
    'message_ar',
    'message_en',
    'notified_users',
])]
class AlertHistory extends Model
{
    use BelongsToTenant;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'triggered_at' => 'datetime',
            'metric_value' => 'decimal:2',
            'threshold_value' => 'decimal:2',
            'notified_users' => 'array',
        ];
    }

    // ──────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function alertRule(): BelongsTo
    {
        return $this->belongsTo(AlertRule::class);
    }
}
