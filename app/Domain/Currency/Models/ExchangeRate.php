<?php

declare(strict_types=1);

namespace App\Domain\Currency\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['base_currency', 'target_currency', 'rate', 'effective_date', 'source'])]
class ExchangeRate extends Model
{
    protected function casts(): array
    {
        return ['rate' => 'decimal:6', 'effective_date' => 'date'];
    }

    /**
     * Get the latest exchange rate between two currencies.
     */
    public static function getRate(string $from, string $to, ?string $date = null): ?float
    {
        if ($from === $to) {
            return 1.0;
        }

        $date = $date ?? now()->toDateString();

        // Direct rate
        $rate = static::where('base_currency', $from)
            ->where('target_currency', $to)
            ->where('effective_date', '<=', $date)
            ->orderByDesc('effective_date')
            ->value('rate');

        if ($rate) {
            return (float) $rate;
        }

        // Inverse rate
        $inverseRate = static::where('base_currency', $to)
            ->where('target_currency', $from)
            ->where('effective_date', '<=', $date)
            ->orderByDesc('effective_date')
            ->value('rate');

        if ($inverseRate && $inverseRate > 0) {
            return round(1 / (float) $inverseRate, 6);
        }

        return null;
    }
}
