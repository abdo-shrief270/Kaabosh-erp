<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Document\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Document */
class DocumentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'disk' => $this->disk,
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
            'size_human' => $this->sizeForHumans(),
            'hash' => $this->hash,
            'category' => $this->category?->value,
            'storage_tier' => $this->storage_tier?->value,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'is_archived' => $this->is_archived,
            'archived_at' => $this->archived_at?->toISOString(),
            'uploaded_by' => $this->uploaded_by,
            'client_id' => $this->client_id,
            'download_url' => route('documents.download', $this->id),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Conditional relations
            'client' => new ClientResource($this->whenLoaded('client')),
            'uploaded_by_user' => $this->whenLoaded('uploadedByUser', fn () => [
                'id' => $this->uploadedByUser->id,
                'name' => $this->uploadedByUser->name,
            ]),
        ];
    }
}
