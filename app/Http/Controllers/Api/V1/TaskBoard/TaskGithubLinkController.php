<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskGithubLink;
use App\Domain\TaskBoard\Services\GithubPrFetcher;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Per-task GitHub PR links. Attach + remove only — status updates are
 * pulled on attach and then refreshed by the nightly cron (or on demand
 * via `refresh`).
 */
class TaskGithubLinkController extends Controller
{
    public function __construct(private readonly GithubPrFetcher $github) {}

    public function index(Request $request, Task $task): JsonResponse
    {
        $links = TaskGithubLink::query()
            ->where('task_id', $task->id)
            ->orderBy('id')
            ->get();

        return response()->json(['data' => $links->map(fn ($l) => $this->present($l))]);
    }

    public function store(Request $request, Task $task): JsonResponse
    {
        $data = $request->validate([
            'url' => ['required', 'string', 'max:500'],
        ]);

        $parsed = $this->github->parseUrl($data['url']);
        if (! $parsed) {
            abort(422, 'Not a recognisable GitHub PR URL. Expected github.com/owner/repo/pull/123.');
        }

        $link = TaskGithubLink::firstOrCreate([
            'task_id' => $task->id,
            'repo' => $parsed['repo'],
            'pr_number' => $parsed['pr_number'],
        ], [
            'tenant_id' => $task->tenant_id,
            'url' => $data['url'],
            'state' => TaskGithubLink::STATE_UNKNOWN,
            'created_by_user_id' => $request->user()?->id,
        ]);

        // Pull metadata synchronously so the UI shows the right state on
        // first render. The job-based refresh keeps it fresh thereafter.
        $this->github->refresh($link);

        return response()->json(['data' => $this->present($link->fresh())], 201);
    }

    public function destroy(Request $request, TaskGithubLink $githubLink): JsonResponse
    {
        abort_unless($githubLink->tenant_id === (int) app('tenant.id'), 403);
        $githubLink->delete();
        return response()->json(['ok' => true]);
    }

    public function refresh(Request $request, TaskGithubLink $githubLink): JsonResponse
    {
        abort_unless($githubLink->tenant_id === (int) app('tenant.id'), 403);
        $this->github->refresh($githubLink);
        return response()->json(['data' => $this->present($githubLink->fresh())]);
    }

    /** @return array<string, mixed> */
    private function present(TaskGithubLink $l): array
    {
        return [
            'id' => $l->id,
            'task_id' => $l->task_id,
            'repo' => $l->repo,
            'pr_number' => $l->pr_number,
            'url' => $l->url,
            'state' => $l->state,
            'title' => $l->title,
            'author' => $l->author,
            'last_fetched_at' => $l->last_fetched_at,
        ];
    }
}
