<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Models;

use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Table('plans')]
#[Fillable([
    'name_en',
    'name_ar',
    'slug',
    'description_en',
    'description_ar',
    'price_monthly',
    'price_annual',
    'currency',
    'trial_days',
    'limits',
    'features',
    'is_active',
    'sort_order',
])]
class Plan extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_annual' => 'decimal:2',
            'limits' => 'array',
            'features' => 'array',
            'is_active' => 'boolean',
            'trial_days' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /** @var array<string, mixed> */
    protected $attributes = [
        'currency' => 'EGP',
        'trial_days' => 14,
        'is_active' => true,
        'sort_order' => 0,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name_en', 'price_monthly', 'price_annual', 'is_active'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Plan {$eventName}");
    }

    // ──────────────────────────────────────
    // Factory
    // ──────────────────────────────────────

    protected static function newFactory(): PlanFactory
    {
        return PlanFactory::new();
    }

    // ──────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    // ──────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeBySlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }

    // ──────────────────────────────────────
    // Helper Methods
    // ──────────────────────────────────────

    public function getLimit(string $key, mixed $default = null): mixed
    {
        return $this->limits[$key] ?? $default;
    }

    public function hasFeature(string $key): bool
    {
        return (bool) ($this->features[$key] ?? false);
    }

    public function priceForCycle(string $cycle): string
    {
        return match ($cycle) {
            'annual' => (string) $this->price_annual,
            default => (string) $this->price_monthly,
        };
    }

    public function monthlyEquivalent(): string
    {
        if ((float) $this->price_annual > 0) {
            return (string) round((float) $this->price_annual / 12, 2);
        }

        return (string) $this->price_monthly;
    }
}
