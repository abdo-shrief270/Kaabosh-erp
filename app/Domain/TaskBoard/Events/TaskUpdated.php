<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Events;

use App\Domain\TaskBoard\Models\Task;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<int, string>  $dirty  Field names that changed; receivers may use
     *                                     this to decide whether to refetch (e.g. title
     *                                     change vs. just `updated_at`).
     */
    public function __construct(
        public readonly Task $task,
        public readonly array $dirty = [],
        public readonly ?int $actorId = null,
    ) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('board.'.$this->task->board_id)];
    }

    public function broadcastAs(): string
    {
        return 'task.updated';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'task_id' => $this->task->id,
            'board_id' => $this->task->board_id,
            'dirty' => array_values($this->dirty),
            'actor_id' => $this->actorId,
        ];
    }
}
