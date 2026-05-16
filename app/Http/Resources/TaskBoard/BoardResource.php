<?php

declare(strict_types=1);

namespace App\Http\Resources\TaskBoard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'color' => $this->color,
            'icon' => $this->icon,
            'visibility' => $this->visibility?->value,
            'key' => $this->key,
            'is_default' => $this->is_default,
            'is_archived' => $this->is_archived,
            'next_task_number' => $this->next_task_number,
            'auto_archive_completed_after_days' => $this->auto_archive_completed_after_days,
            'inbox_key' => $this->inbox_key,
            'inbox_enabled' => (bool) $this->inbox_enabled,
            // Synthesised email address — only shown when inbox is enabled.
            // Domain matches the app host so customer DNS handles routing.
            'inbox_email' => $this->inbox_key && $this->inbox_enabled
                ? 'tasks+'.$this->inbox_key.'@'.parse_url((string) config('app.url'), PHP_URL_HOST)
                : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'columns' => BoardColumnResource::collection($this->whenLoaded('columns')),
            'tasks_count' => $this->whenCounted('tasks'),
            // The requester's effective access level on this board, resolved
            // by BoardAccessService. Lets the SPA hide admin actions for
            // editors/viewers without round-tripping per click.
            'my_level' => $this->resolveMyLevel($request),
        ];
    }

    private function resolveMyLevel(Request $request): ?string
    {
        $user = $request->user();
        if (! $user) return null;
        try {
            /** @var \App\Domain\TaskBoard\Services\BoardAccessService $svc */
            $svc = app(\App\Domain\TaskBoard\Services\BoardAccessService::class);
            return $svc->levelFor($this->resource, $user);
        } catch (\Throwable) {
            return null;
        }
    }
}
