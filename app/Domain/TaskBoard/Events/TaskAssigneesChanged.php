<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Events;

use App\Domain\TaskBoard\Models\Task;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskAssigneesChanged
{
    use Dispatchable, SerializesModels;

    /**
     * @param  int[]  $added
     * @param  int[]  $removed
     */
    public function __construct(
        public readonly Task $task,
        public readonly array $added,
        public readonly array $removed,
        public readonly ?int $actorId = null,
    ) {}
}
