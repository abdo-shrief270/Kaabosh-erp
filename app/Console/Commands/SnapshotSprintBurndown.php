<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\TaskBoard\Enums\SprintStatus;
use App\Domain\TaskBoard\Models\Sprint;
use App\Domain\TaskBoard\Services\SprintService;
use Illuminate\Console\Command;

class SnapshotSprintBurndown extends Command
{
    protected $signature = 'task-board:snapshot-burndown';

    protected $description = 'Record a daily burndown snapshot for every active sprint.';

    public function handle(SprintService $service): int
    {
        $active = Sprint::query()->withoutGlobalScopes()->where('status', SprintStatus::Active->value)->get();
        foreach ($active as $sprint) {
            try {
                $service->snapshot($sprint);
            } catch (\Throwable $e) {
                $this->error("Snapshot failed for sprint {$sprint->id}: {$e->getMessage()}");
            }
        }
        $this->info("Snapshotted {$active->count()} active sprint(s).");

        return self::SUCCESS;
    }
}
