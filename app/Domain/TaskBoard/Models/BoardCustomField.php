<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoardCustomField extends Model
{
    use BelongsToTenant;

    public const TYPES = ['text', 'number', 'date', 'select', 'multi_select', 'url', 'checkbox'];

    protected $fillable = [
        'tenant_id', 'board_id', 'key', 'label', 'type',
        'options', 'required', 'position',
    ];

    protected $casts = [
        'options' => 'array',
        'required' => 'boolean',
        'position' => 'float',
    ];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(TaskCustomFieldValue::class, 'custom_field_id');
    }
}
