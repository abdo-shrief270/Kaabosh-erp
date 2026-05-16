<?php

declare(strict_types=1);

use App\Domain\TaskBoard\Models\Board;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/**
 * Real-time channel authorisation.
 *
 *   board.{boardId} — private channel; auth allowed only if the user
 *                     belongs to the same tenant as the board. We don't
 *                     do per-board ACL yet (boards are tenant-wide), so
 *                     tenant-match is the right check.
 */
Broadcast::channel('board.{boardId}', function (User $user, int $boardId) {
    /** @var Board|null $board */
    $board = Board::query()
        ->withoutGlobalScopes()
        ->where('id', $boardId)
        ->where('tenant_id', $user->tenant_id)
        ->first();
    if (! $board) {
        return false;
    }
    return ['id' => $user->id, 'name' => $user->name];
});
