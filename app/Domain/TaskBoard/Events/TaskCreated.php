<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Events;

use App\Domain\TaskBoard\Models\Task;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Task $task,
        public readonly ?int $actorId = null,
    ) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('board.'.$this->task->board_id)];
    }

    public function broadcastAs(): string
    {
        return 'task.created';
    }

    /**
     * Minimal wire payload — clients refetch the task for full detail to
     * keep this lean. `actor_id` lets the receiver skip its own echo.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'task_id' => $this->task->id,
            'board_id' => $this->task->board_id,
            'board_column_id' => $this->task->board_column_id,
            'actor_id' => $this->actorId,
        ];
    }
}
