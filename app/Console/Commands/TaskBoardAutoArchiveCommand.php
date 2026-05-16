<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\TaskBoard\Models\Board;
use App\Domain\TaskBoard\Models\Task;
use Illuminate\Console\Command;

/**
 * Auto-archive stale completed tasks. A board with
 * `auto_archive_completed_after_days = N` archives any of its tasks that
 * have been sitting with `completed_at <= now - N days` and aren't
 * already archived. Run nightly via the scheduler.
 *
 * Designed to be idempotent: re-running it the same day does nothing on
 * tasks it just archived.
 */
class TaskBoardAutoArchiveCommand extends Command
{
    protected $signature = 'task-board:auto-archive
        {--dry-run : Report counts without writing}
        {--board= : Run only for a specific board id}';

    protected $description = 'Archive completed task-board tasks per each board\'s auto-archive policy';

    public function handle(): int
    {
        $now = now();
        $totalArchived = 0;
        $boardsRun = 0;

        $boardQuery = Board::query()
            ->whereNotNull('auto_archive_completed_after_days')
            ->where('auto_archive_completed_after_days', '>', 0);

        if ($id = (int) $this->option('board')) {
            $boardQuery->whereKey($id);
        }

        $dry = (bool) $this->option('dry-run');

        $boardQuery->chunkById(100, function ($boards) use (&$totalArchived, &$boardsRun, $now, $dry) {
            foreach ($boards as $board) {
                /** @var Board $board */
                $cutoff = $now->copy()->subDays((int) $board->auto_archive_completed_after_days);
                $query = Task::query()
                    ->where('board_id', $board->id)
                    ->whereNotNull('completed_at')
                    ->whereNull('archived_at')
                    ->where('completed_at', '<=', $cutoff);

                $count = $query->count();
                if ($count === 0) {
                    continue;
                }

                $this->info(sprintf(
                    'Board #%d "%s": %d task(s) older than %d day(s) since completion',
                    $board->id, $board->name, $count, $board->auto_archive_completed_after_days,
                ));

                if (! $dry) {
                    $query->update(['archived_at' => $now]);
                }

                $totalArchived += $count;
                $boardsRun++;
            }
        });

        $this->line('');
        $this->info(sprintf(
            '%s%d task(s) across %d board(s).',
            $dry ? '[dry-run] would archive ' : 'Archived ',
            $totalArchived, $boardsRun,
        ));

        return self::SUCCESS;
    }
}
