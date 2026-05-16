<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $taskId,
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
        return 'task.deleted';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'task_id' => $this->taskId,
            'board_id' => $this->boardId,
            'actor_id' => $this->actorId,
        ];
    }
}
