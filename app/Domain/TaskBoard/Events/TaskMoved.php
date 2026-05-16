<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Events;

use App\Domain\TaskBoard\Models\Task;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskMoved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Task $task,
        public readonly int $fromColumnId,
        public readonly int $toColumnId,
        public readonly bool $enteredDoneColumn,
        public readonly ?int $actorId = null,
    ) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('board.'.$this->task->board_id)];
    }

    public function broadcastAs(): string
    {
        return 'task.moved';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'task_id' => $this->task->id,
            'board_id' => $this->task->board_id,
            'from_column_id' => $this->fromColumnId,
            'to_column_id' => $this->toColumnId,
            'position' => (float) $this->task->position,
            'completed_at' => $this->task->completed_at,
            'actor_id' => $this->actorId,
        ];
    }
}
