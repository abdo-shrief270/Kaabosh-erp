<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $subscription_add_on_id
 * @property string $kind
 * @property int $quantity_total
 * @property int $quantity_used
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read SubscriptionAddOn|null $subscriptionAddOn
 */
#[Table('add_on_credits')]
#[Fillable([
    'tenant_id',
    'subscription_add_on_id',
    'kind',
    'quantity_total',
    'quantity_used',
    'expires_at',
    'metadata',
])]
class AddOnCredit extends Model
{
    use BelongsToTenant;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'quantity_total' => 'integer',
            'quantity_used' => 'integer',
            'expires_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /** @var array<string, mixed> */
    protected $attributes = [
        'quantity_used' => 0,
    ];

    // ──────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────

    public function subscriptionAddOn(): BelongsTo
    {
        return $this->belongsTo(SubscriptionAddOn::class);
    }

    // ──────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────

    public function scopeOfKind(Builder $query, string $kind): Builder
    {
        return $query->where('kind', $kind);
    }

    /**
     * Credits that still have unused balance and haven't expired.
     */
    public function scopeUsable(Builder $query): Builder
    {
        return $query
            ->whereColumn('quantity_used', '<', 'quantity_total')
            ->where(function (Builder $q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    // ──────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────

    public function remaining(): int
    {
        return max(0, ($this->quantity_total ?? 0) - ($this->quantity_used ?? 0));
    }
}
