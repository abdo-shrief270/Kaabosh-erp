<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use App\Domain\Subscription\Enums\PaymentGateway;
use App\Domain\Subscription\Enums\PaymentStatus;
use App\Domain\Tenant\Models\Tenant;
use Database\Factories\SubscriptionPaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Table('subscription_payments')]
#[Fillable([
    'tenant_id',
    'subscription_id',
    'amount',
    'currency',
    'status',
    'gateway',
    'gateway_transaction_id',
    'gateway_order_id',
    'payment_method_type',
    'billing_period_start',
    'billing_period_end',
    'paid_at',
    'failed_at',
    'failure_reason',
    'refunded_at',
    'receipt_url',
    'metadata',
])]
class SubscriptionPayment extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use LogsActivity;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PaymentStatus::class,
            'gateway' => PaymentGateway::class,
            'billing_period_start' => 'date',
            'billing_period_end' => 'date',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
            'refunded_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /** @var array<string, mixed> */
    protected $attributes = [
        'currency' => 'EGP',
        'status' => 'pending',
    ];

    // ──────────────────────────────────────
    // Factory
    // ──────────────────────────────────────

    protected static function newFactory(): SubscriptionPaymentFactory
    {
        return SubscriptionPaymentFactory::new();
    }

    // ──────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    // ──────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::Completed);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::Failed);
    }

    public function scopeForGateway(Builder $query, PaymentGateway $gateway): Builder
    {
        return $query->where('gateway', $gateway);
    }

    // ──────────────────────────────────────
    // Helper Methods
    // ──────────────────────────────────────

    public function isCompleted(): bool
    {
        return $this->status === PaymentStatus::Completed;
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::Pending;
    }

    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::Failed;
    }

    // ──────────────────────────────────────
    // Activity Log
    // ──────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['amount', 'status', 'gateway'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
