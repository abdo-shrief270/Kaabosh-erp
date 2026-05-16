<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Document\Models\StorageQuota;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StorageQuota */
class StorageQuotaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'max_bytes' => $this->max_bytes,
            'used_bytes' => $this->used_bytes,
            'max_files' => $this->max_files,
            'used_files' => $this->used_files,
            'usage_percent' => round($this->usagePercent(), 2),
            'remaining_bytes' => $this->remainingBytes(),
            'max_bytes_human' => $this->maxBytesForHumans(),
            'used_bytes_human' => $this->usedBytesForHumans(),
        ];
    }
}
