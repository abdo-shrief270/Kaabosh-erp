<?php

declare(strict_types=1);

namespace App\Domain\Onboarding\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('coa_templates')]
#[Fillable([
    'name_ar',
    'name_en',
    'industry',
    'accounts',
    'is_default',
])]
class CoaTemplate extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'accounts' => 'array',
            'is_default' => 'boolean',
        ];
    }
}
