<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Document\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Document */
class PortalDocumentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
            'category' => $this->category?->value ?? $this->category,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
