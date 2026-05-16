<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Enums\VersionStatus;
use App\Domain\TaskBoard\Models\Version;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaskBoard\VersionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class VersionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Version::query()->withCount('tasks')->orderBy('release_date');
        if ($boardId = $request->integer('board_id')) {
            $query->where('board_id', $boardId);
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return VersionResource::collection($query->get());
    }

    public function store(Request $request): JsonResource
    {
        abort_unless($request->user()?->can('manage_versions'), 403);

        $data = $request->validate([
            'board_id' => ['required', 'integer', 'exists:boards,id'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'in:planned,in_progress,released,archived'],
            'release_date' => ['nullable', 'date'],
            'color' => ['nullable', 'string', 'max:9'],
        ]);
        $data['slug'] = Str::slug($data['name']);
        $data['status'] ??= VersionStatus::Planned->value;

        $version = Version::create($data);

        return VersionResource::make($version);
    }

    public function update(Request $request, Version $version): JsonResource
    {
        abort_unless($request->user()?->can('manage_versions'), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['sometimes', 'in:planned,in_progress,released,archived'],
            'release_date' => ['nullable', 'date'],
            'color' => ['nullable', 'string', 'max:9'],
        ]);

        // Auto-stamp released_at when transitioning to released
        if (($data['status'] ?? null) === VersionStatus::Released->value && ! $version->released_at) {
            $data['released_at'] = now();
        }

        $version->update($data);

        return VersionResource::make($version->fresh());
    }

    public function destroy(Request $request, Version $version)
    {
        abort_unless($request->user()?->can('manage_versions'), 403);
        $version->delete();

        return response()->noContent();
    }
}
