<?php

declare(strict_types=1);

namespace App\Domain\Currency\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'name_ar', 'name_en', 'symbol', 'decimal_places', 'is_active'])]
class Currency extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'decimal_places' => 'integer'];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Format an amount with this currency's symbol and decimal places.
     */
    public function format(float $amount): string
    {
        return number_format($amount, $this->decimal_places).' '.$this->symbol;
    }
}
