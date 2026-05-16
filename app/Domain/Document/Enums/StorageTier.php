<?php

declare(strict_types=1);

namespace App\Domain\Document\Enums;

enum StorageTier: string
{
    case Hot = 'hot';
    case Warm = 'warm';
    case Cold = 'cold';

    public function label(): string
    {
        return match ($this) {
            self::Hot => 'Hot',
            self::Warm => 'Warm',
            self::Cold => 'Cold',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::Hot => 'ساخن',
            self::Warm => 'دافئ',
            self::Cold => 'بارد',
        };
    }
}
