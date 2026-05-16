<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;

class CleanupTokensCommand extends Command
{
    protected $signature = 'tokens:cleanup {--days=7 : Delete tokens older than N days}';

    protected $description = 'Clean up expired and old impersonation tokens';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        // Delete impersonation tokens older than 1 day
        $impersonationDeleted = PersonalAccessToken::query()
            ->where('name', 'like', 'impersonation-%')
            ->where('created_at', '<', now()->subDay())
            ->delete();

        // Delete all tokens older than N days
        $oldDeleted = PersonalAccessToken::query()
            ->where('created_at', '<', now()->subDays($days))
            ->whereNull('last_used_at')
            ->delete();

        // Delete tokens with expires_at in the past
        $expiredDeleted = PersonalAccessToken::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->delete();

        $total = $impersonationDeleted + $oldDeleted + $expiredDeleted;
        $this->info("Cleaned up {$total} tokens (impersonation: {$impersonationDeleted}, old: {$oldDeleted}, expired: {$expiredDeleted}).");

        return self::SUCCESS;
    }
}
