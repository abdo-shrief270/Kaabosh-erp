<?php

declare(strict_types=1);

namespace App\Http\Resources\TaskBoard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

class TaskAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'comment_id' => $this->comment_id,
            'filename' => $this->filename,
            'original_filename' => $this->original_filename,
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
            'human_size' => $this->humanSize(),
            'created_at' => $this->created_at,
            'uploader' => $this->whenLoaded('uploader', fn () => [
                'id' => $this->uploader?->id,
                'name' => $this->uploader?->name,
            ]),
            // Short-lived signed URL — the client can re-fetch when expired.
            'download_url' => URL::temporarySignedRoute(
                'tasks.attachments.download',
                now()->addMinutes(10),
                ['attachment' => $this->id],
            ),
        ];
    }
}
