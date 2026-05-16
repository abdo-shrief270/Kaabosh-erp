<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\TaskBoard\Services\RecurrenceSpawner;
use Illuminate\Console\Command;

class SpawnRecurringTasks extends Command
{
    protected $signature = 'task-board:spawn-recurrences';

    protected $description = 'Spawn concrete tasks for every recurrence whose next_spawn_at has elapsed.';

    public function handle(RecurrenceSpawner $spawner): int
    {
        $count = $spawner->spawnDue();
        $this->info("Spawned $count recurring tasks.");

        return self::SUCCESS;
    }
}
