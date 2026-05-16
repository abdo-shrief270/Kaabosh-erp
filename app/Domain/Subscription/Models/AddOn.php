<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Models;

use App\Domain\Subscription\Enums\AddOnBillingCycle;
use App\Domain\Subscription\Enums\AddOnType;
use Database\Factories\AddOnFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $slug
 * @property string $name_en
 * @property string $name_ar
 * @property string|null $description_en
 * @property string|null $description_ar
 * @property AddOnType $type
 * @property AddOnBillingCycle $billing_cycle
 * @property array<string, int>|null $boost
 * @property string|null $feature_slug
 * @property string|null $credit_kind
 * @property int|null $credit_quantity
 * @property string $price_monthly
 * @property string $price_annual
 * @property string $price_once
 * @property string $currency
 * @property bool $is_active
 * @property int $sort_order
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
#[Table('add_ons')]
#[Fillable([
    'slug',
    'name_en',
    'name_ar',
    'description_en',
    'description_ar',
    'type',
    'billing_cycle',
    'boost',
    'feature_slug',
    'credit_kind',
    'credit_quantity',
    'price_monthly',
    'price_annual',
    'price_once',
    'currency',
    'is_active',
    'sort_order',
    'metadata',
])]
class AddOn extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => AddOnType::class,
            'billing_cycle' => AddOnBillingCycle::class,
            'boost' => 'array',
            'credit_quantity' => 'integer',
            'price_monthly' => 'decimal:2',
            'price_annual' => 'decimal:2',
            'price_once' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    /** @var array<string, mixed> */
    protected $attributes = [
        'currency' => 'EGP',
        'is_active' => true,
        'sort_order' => 0,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['slug', 'name_en', 'price_monthly', 'price_annual', 'price_once', 'is_active'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "AddOn {$eventName}");
    }

    protected static function newFactory(): AddOnFactory
    {
        return AddOnFactory::new();
    }

    // ──────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────

    public function subscriptionAddOns(): HasMany
    {
        return $this->hasMany(SubscriptionAddOn::class);
    }

    // ──────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType(Builder $query, AddOnType|string $type): Builder
    {
        return $query->where('type', $type instanceof AddOnType ? $type->value : $type);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    // ──────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────

    /**
     * Price for a given billing cycle. Falls back to price_once for the
     * Once cycle, monthly otherwise.
     */
    public function priceForCycle(AddOnBillingCycle|string $cycle): string
    {
        $cycle = $cycle instanceof AddOnBillingCycle ? $cycle : AddOnBillingCycle::from($cycle);

        return match ($cycle) {
            AddOnBillingCycle::Annual => (string) $this->price_annual,
            AddOnBillingCycle::Once => (string) $this->price_once,
            default => (string) $this->price_monthly,
        };
    }
}
