<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Services;

use App\Domain\TaskBoard\Models\TaskGithubLink;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetches PR metadata from the GitHub REST API and refreshes a cached
 * link row. Uses ETag for conditional GETs so we get free 304s on
 * unchanged PRs — keeps anonymous usage well under the 60/hr limit.
 *
 * Configuration: set `services.github.token` to a PAT/install token for
 * higher rate limits and access to private repos. Without it we still
 * work for public repos at the anonymous rate.
 */
class GithubPrFetcher
{
    private const URL_REGEX = '#^https?://(?:www\.)?github\.com/([\w.\-]+)/([\w.\-]+)/pull/(\d+)#i';

    /**
     * Parse a GitHub PR URL into (repo, pr_number). Returns null on shapes
     * we don't accept — the controller turns that into a 422.
     *
     * @return array{repo: string, pr_number: int}|null
     */
    public function parseUrl(string $url): ?array
    {
        if (! preg_match(self::URL_REGEX, trim($url), $m)) {
            return null;
        }
        return ['repo' => $m[1].'/'.$m[2], 'pr_number' => (int) $m[3]];
    }

    /**
     * Refresh `$link` from the GitHub API. Updates state/title/author/etag
     * and last_fetched_at. Soft-fails on transient errors so a flaky
     * GitHub doesn't break the task UI; we just leave the cached state
     * in place.
     */
    public function refresh(TaskGithubLink $link): void
    {
        $token = config('services.github.token');
        $headers = [
            'Accept' => 'application/vnd.github+json',
            'User-Agent' => 'Kaabosh/1.0',
            'X-GitHub-Api-Version' => '2022-11-28',
        ];
        if ($token) $headers['Authorization'] = 'Bearer '.$token;
        if ($link->etag) $headers['If-None-Match'] = $link->etag;

        try {
            $response = Http::timeout(8)
                ->withHeaders($headers)
                ->get("https://api.github.com/repos/{$link->repo}/pulls/{$link->pr_number}");
        } catch (\Throwable $e) {
            Log::warning('GitHub PR fetch failed', ['link_id' => $link->id, 'err' => $e->getMessage()]);
            return;
        }

        $link->last_fetched_at = now();

        if ($response->status() === 304) {
            // Unchanged — refresh the timestamp only.
            $link->save();
            return;
        }

        if ($response->status() === 404) {
            // PR deleted on GitHub's side or repo went private/inaccessible.
            $link->state = TaskGithubLink::STATE_UNKNOWN;
            $link->save();
            return;
        }

        if ($response->failed()) {
            // Rate-limited or 5xx — log and bail, keep cached state.
            Log::warning('GitHub PR fetch non-2xx', [
                'link_id' => $link->id,
                'status' => $response->status(),
            ]);
            return;
        }

        $data = $response->json();
        $state = match (true) {
            ($data['merged_at'] ?? null) !== null => TaskGithubLink::STATE_MERGED,
            ($data['draft'] ?? false) === true && ($data['state'] ?? '') === 'open' => TaskGithubLink::STATE_DRAFT,
            ($data['state'] ?? '') === 'open' => TaskGithubLink::STATE_OPEN,
            ($data['state'] ?? '') === 'closed' => TaskGithubLink::STATE_CLOSED,
            default => TaskGithubLink::STATE_UNKNOWN,
        };

        $link->state = $state;
        $link->title = mb_substr((string) ($data['title'] ?? ''), 0, 500);
        $link->author = mb_substr((string) ($data['user']['login'] ?? ''), 0, 80);
        $link->etag = (string) ($response->header('ETag') ?: $link->etag);
        $link->save();
    }
}
