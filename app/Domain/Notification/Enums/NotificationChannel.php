<?php

declare(strict_types=1);

namespace App\Domain\Notification\Enums;

enum NotificationChannel: string
{
    case InApp = 'in_app';
    case Email = 'email';
    case Push = 'push';
    case Both = 'both';
    case All = 'all';

    public function label(): string
    {
        return match ($this) {
            self::InApp => 'In-App',
            self::Email => 'Email',
            self::Push => 'Push Notification',
            self::Both => 'In-App + Email',
            self::All => 'All Channels',
        };
    }

    /**
     * Which delivery mechanisms this channel includes.
     *
     * @return array<string>
     */
    public function mechanisms(): array
    {
        return match ($this) {
            self::InApp => ['database'],
            self::Email => ['email'],
            self::Push => ['push'],
            self::Both => ['database', 'email'],
            self::All => ['database', 'email', 'push'],
        };
    }
}
