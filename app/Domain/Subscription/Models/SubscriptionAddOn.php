<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use App\Domain\Subscription\Enums\AddOnBillingCycle;
use App\Domain\Subscription\Enums\PaymentGateway;
use App\Domain\Subscription\Enums\SubscriptionAddOnStatus;
use Database\Factories\SubscriptionAddOnFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $subscription_id
 * @property int $add_on_id
 * @property int $quantity
 * @property SubscriptionAddOnStatus $status
 * @property AddOnBillingCycle $billing_cycle
 * @property string $price
 * @property string $currency
 * @property \Illuminate\Support\Carbon|null $current_period_start
 * @property \Illuminate\Support\Carbon|null $current_period_end
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 * @property bool $cancel_at_period_end
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property PaymentGateway|null $gateway
 * @property string|null $gateway_payment_id
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read AddOn|null $addOn
 * @property-read Subscription|null $subscription
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AddOnCredit> $credits
 */
#[Table('subscription_add_ons')]
#[Fillable([
    'firm_id',
    'tenant_id',
    'subscription_id',
    'add_on_id',
    'quantity',
    'status',
    'billing_cycle',
    'price',
    'currency',
    'current_period_start',
    'current_period_end',
    'cancelled_at',
    'cancel_at_period_end',
    'expires_at',
    'gateway',
    'gateway_payment_id',
    'metadata',
])]
class SubscriptionAddOn extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use LogsActivity;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => SubscriptionAddOnStatus::class,
            'billing_cycle' => AddOnBillingCycle::class,
            'gateway' => PaymentGateway::class,
            'quantity' => 'integer',
            'price' => 'decimal:2',
            'current_period_start' => 'date',
            'current_period_end' => 'date',
            'cancelled_at' => 'datetime',
            'cancel_at_period_end' => 'boolean',
            'expires_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /** @var array<string, mixed> */
    protected $attributes = [
        'quantity' => 1,
        'status' => 'active',
        'billing_cycle' => 'monthly',
        'cancel_at_period_end' => false,
        'currency' => 'EGP',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['add_on_id', 'quantity', 'status', 'price', 'cancel_at_period_end'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "SubscriptionAddOn {$eventName}");
    }

    protected static function newFactory(): SubscriptionAddOnFactory
    {
        return SubscriptionAddOnFactory::new();
    }

    // ──────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /** @return BelongsTo<\App\Domain\Firm\Models\Firm, $this> */
    public function firm(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Firm\Models\Firm::class);
    }

    public function addOn(): BelongsTo
    {
        return $this->belongsTo(AddOn::class);
    }

    public function credits(): HasMany
    {
        return $this->hasMany(AddOnCredit::class);
    }

    // ──────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', SubscriptionAddOnStatus::Active->value);
    }

    public function scopeForSubscription(Builder $query, int $subscriptionId): Builder
    {
        return $query->where('subscription_id', $subscriptionId);
    }

    public function scopeForFirm(Builder $query, int $firmId): Builder
    {
        return $query->withoutGlobalScopes()->where('firm_id', $firmId);
    }

    // ──────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────

    public function isActive(): bool
    {
        if ($this->status !== SubscriptionAddOnStatus::Active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}
