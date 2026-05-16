<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Models;

use App\Domain\Subscription\Enums\DiscountType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Table('coupons')]
#[Fillable([
    'code',
    'description',
    'discount_type',
    'discount_value',
    'currency',
    'max_uses',
    'used_count',
    'applies_to_plan_ids',
    'expires_at',
    'is_active',
])]
class Coupon extends Model
{
    use LogsActivity;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'discount_type' => DiscountType::class,
            'discount_value' => 'decimal:2',
            'applies_to_plan_ids' => 'array',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'max_uses' => 'integer',
            'used_count' => 'integer',
        ];
    }

    public function scopeRedeemable(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function (Builder $q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->where(function (Builder $q): void {
                $q->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses');
            });
    }

    /** Whether this coupon is valid for the given plan id. */
    public function appliesToPlan(int $planId): bool
    {
        return $this->applies_to_plan_ids === null
            || in_array($planId, $this->applies_to_plan_ids, true);
    }

    /** Compute discount in absolute currency units for a given gross price. */
    public function discountFor(float $price): float
    {
        $value = (float) $this->discount_value;

        $discount = $this->discount_type === DiscountType::Percent
            ? $price * ($value / 100.0)
            : $value;

        return (float) min(round($discount, 2), $price);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'discount_type', 'discount_value', 'is_active', 'used_count'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
