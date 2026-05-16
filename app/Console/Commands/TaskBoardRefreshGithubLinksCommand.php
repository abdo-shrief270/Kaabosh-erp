<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\TaskBoard\Models\TaskGithubLink;
use App\Domain\TaskBoard\Services\GithubPrFetcher;
use Illuminate\Console\Command;

/**
 * Periodically refresh cached GitHub PR metadata. Uses ETag conditional
 * GETs so most refreshes are free 304s. Run every 15 minutes via the
 * scheduler; bound the per-run set to 500 oldest rows to keep the worker
 * light on tenants with thousands of links.
 */
class TaskBoardRefreshGithubLinksCommand extends Command
{
    protected $signature = 'task-board:refresh-github-links {--limit=500}';
    protected $description = 'Refresh cached state of linked GitHub PRs';

    public function handle(GithubPrFetcher $fetcher): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $links = TaskGithubLink::query()
            ->orderByRaw('last_fetched_at NULLS FIRST')
            ->limit($limit)
            ->get();

        foreach ($links as $link) {
            $fetcher->refresh($link);
        }

        $this->info('Refreshed '.$links->count().' GitHub link(s).');
        return self::SUCCESS;
    }
}
