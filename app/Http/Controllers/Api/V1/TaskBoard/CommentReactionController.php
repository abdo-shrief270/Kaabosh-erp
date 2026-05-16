<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Models\CommentReaction;
use App\Domain\TaskBoard\Models\TaskComment;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentReactionController extends Controller
{
    /**
     * Toggle one (user, emoji) reaction on a comment. Returns the post-state
     * roll-up grouped by emoji so the UI can render counts + "did I react"
     * without a second round-trip.
     */
    public function toggle(Request $request, TaskComment $comment): JsonResponse
    {
        abort_unless($request->user()?->can('comment_tasks'), 403);

        $data = $request->validate([
            'emoji' => ['required', 'string', 'max:16'],
        ]);

        $userId = (int) $request->user()->id;
        $existing = CommentReaction::query()
            ->where('comment_id', $comment->id)
            ->where('user_id', $userId)
            ->where('emoji', $data['emoji'])
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            CommentReaction::create([
                'tenant_id' => $comment->tenant_id,
                'comment_id' => $comment->id,
                'user_id' => $userId,
                'emoji' => $data['emoji'],
                'created_at' => now(),
            ]);
        }

        return response()->json(['data' => $this->summarise($comment, $userId)]);
    }

    /** @return array<int, array<string, mixed>> */
    private function summarise(TaskComment $comment, int $currentUserId): array
    {
        $rows = CommentReaction::query()
            ->where('comment_id', $comment->id)
            ->get(['emoji', 'user_id']);

        $grouped = [];
        foreach ($rows as $r) {
            $grouped[$r->emoji] ??= ['emoji' => $r->emoji, 'count' => 0, 'mine' => false, 'user_ids' => []];
            $grouped[$r->emoji]['count']++;
            $grouped[$r->emoji]['user_ids'][] = (int) $r->user_id;
            if ((int) $r->user_id === $currentUserId) {
                $grouped[$r->emoji]['mine'] = true;
            }
        }

        return array_values($grouped);
    }
}
