<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Models\Tag;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaskBoard\TagResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Tag::query()->orderBy('name');
        if ($boardId = $request->integer('board_id')) {
            $query->where(fn ($q) => $q->where('board_id', $boardId)->orWhereNull('board_id'));
        }

        return TagResource::collection($query->get());
    }

    public function store(Request $request): JsonResource
    {
        abort_unless($request->user()?->can('manage_tags'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'board_id' => ['nullable', 'integer', 'exists:boards,id'],
            'color' => ['nullable', 'string', 'max:9'],
        ]);
        $data['slug'] = Str::slug($data['name']);
        $data['color'] ??= '#94a3b8';

        $tag = Tag::create($data);

        return TagResource::make($tag);
    }

    public function update(Request $request, Tag $tag): JsonResource
    {
        abort_unless($request->user()?->can('manage_tags'), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:80'],
            'color' => ['nullable', 'string', 'max:9'],
        ]);

        $tag->update($data);

        return TagResource::make($tag->fresh());
    }

    public function destroy(Request $request, Tag $tag)
    {
        abort_unless($request->user()?->can('manage_tags'), 403);
        $tag->delete();

        return response()->noContent();
    }
}
