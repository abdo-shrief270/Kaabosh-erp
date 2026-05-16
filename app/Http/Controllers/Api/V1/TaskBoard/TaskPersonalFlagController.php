<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Models\Task;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Per-user toggles on a task: star (favourite) and mute (suppress
 * notifications). Both share the same pivot shape (task_id, user_id,
 * created_at) so they're driven by one controller with the flag as a route
 * parameter — saves four near-identical action methods.
 */
class TaskPersonalFlagController extends Controller
{
    private const FLAGS = [
        'star' => 'starredBy',
        'mute' => 'mutedBy',
    ];

    public function store(Request $request, Task $task, string $flag): JsonResponse
    {
        abort_unless(array_key_exists($flag, self::FLAGS), 404);
        $userId = (int) $request->user()->id;

        $task->{self::FLAGS[$flag]}()->syncWithoutDetaching([$userId]);

        return response()->json(['flag' => $flag, 'state' => true]);
    }

    public function destroy(Request $request, Task $task, string $flag): JsonResponse
    {
        abort_unless(array_key_exists($flag, self::FLAGS), 404);
        $userId = (int) $request->user()->id;

        $task->{self::FLAGS[$flag]}()->detach($userId);

        return response()->json(['flag' => $flag, 'state' => false]);
    }
}
