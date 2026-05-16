<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Events;

use App\Domain\TaskBoard\Models\TaskComment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentAdded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly TaskComment $comment,
        public readonly int $boardId,
        public readonly ?int $actorId = null,
    ) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('board.'.$this->boardId)];
    }

    public function broadcastAs(): string
    {
        return 'comment.added';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'comment_id' => $this->comment->id,
            'task_id' => $this->comment->task_id,
            'parent_id' => $this->comment->parent_id,
            'board_id' => $this->boardId,
            'actor_id' => $this->actorId,
        ];
    }
}
