<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Enums;

enum BoardVisibility: string
{
    case Private = 'private';
    case Team = 'team';
    case Company = 'company';

    public function label(): string
    {
        return match ($this) {
            self::Private => 'Private',
            self::Team => 'Team',
            self::Company => 'Company',
        };
    }
}
