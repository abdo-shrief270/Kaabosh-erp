<?php

declare(strict_types=1);

namespace App\Http\Resources\TaskBoard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'parent_id' => $this->parent_id,
            'body' => $this->body,
            'mentions' => $this->mentions ?? [],
            'edited_at' => $this->edited_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
            ]),
            'replies' => TaskCommentResource::collection($this->whenLoaded('replies')),
            'attachments' => TaskAttachmentResource::collection($this->whenLoaded('attachments')),
            'reactions' => $this->whenLoaded('reactions', function () use ($request) {
                $currentId = (int) ($request->user()?->id ?? 0);
                $byEmoji = [];
                foreach ($this->reactions as $r) {
                    $byEmoji[$r->emoji] ??= ['emoji' => $r->emoji, 'count' => 0, 'mine' => false, 'user_ids' => []];
                    $byEmoji[$r->emoji]['count']++;
                    $byEmoji[$r->emoji]['user_ids'][] = (int) $r->user_id;
                    if ((int) $r->user_id === $currentId) {
                        $byEmoji[$r->emoji]['mine'] = true;
                    }
                }
                return array_values($byEmoji);
            }),
        ];
    }
}
