<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Models\Board;
use App\Domain\TaskBoard\Models\BoardCustomField;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * Per-board custom field definitions. CRUD is gated to manage_boards. The
 * `key` is auto-derived from `label` on create (slugified) and must be
 * unique within the board so saved API queries can rely on it.
 */
class BoardCustomFieldController extends Controller
{
    public function index(Request $request, Board $board): AnonymousResourceCollection
    {
        abort_unless($request->user()?->can('view_tasks'), 403);

        $fields = BoardCustomField::query()
            ->where('board_id', $board->id)
            ->orderBy('position')
            ->get();

        return \App\Http\Resources\TaskBoard\BoardCustomFieldResource::collection($fields);
    }

    public function store(Request $request, Board $board): JsonResource
    {
        abort_unless($request->user()?->can('manage_boards'), 403);

        $data = $this->validatePayload($request);

        $key = $this->uniqueKey($board, $data['label']);
        $position = ((float) BoardCustomField::where('board_id', $board->id)->max('position')) + 1000;

        $field = BoardCustomField::create([
            'tenant_id' => $board->tenant_id,
            'board_id' => $board->id,
            'key' => $key,
            'label' => $data['label'],
            'type' => $data['type'],
            'options' => $this->normaliseOptions($data),
            'required' => $data['required'] ?? false,
            'position' => $position,
        ]);

        return \App\Http\Resources\TaskBoard\BoardCustomFieldResource::make($field);
    }

    public function update(Request $request, BoardCustomField $customField): JsonResource
    {
        abort_unless($request->user()?->can('manage_boards'), 403);

        $data = $this->validatePayload($request, partial: true);

        if (isset($data['label']) && $data['label'] !== $customField->label) {
            $customField->label = $data['label'];
        }
        // The `type` of a field is locked once values exist — flipping a
        // 'select' to 'number' would corrupt every stored value. Allowed
        // only when no values are saved yet.
        if (isset($data['type']) && $data['type'] !== $customField->type) {
            $hasValues = $customField->values()->exists();
            abort_if($hasValues, 422, 'Cannot change field type while values exist.');
            $customField->type = $data['type'];
        }
        if (array_key_exists('options', $data)) {
            $customField->options = $this->normaliseOptions(array_merge(
                $data,
                ['type' => $data['type'] ?? $customField->type],
            ));
        }
        if (array_key_exists('required', $data)) {
            $customField->required = (bool) $data['required'];
        }
        if (array_key_exists('position', $data)) {
            $customField->position = (float) $data['position'];
        }
        $customField->save();

        return \App\Http\Resources\TaskBoard\BoardCustomFieldResource::make($customField->fresh());
    }

    public function destroy(Request $request, BoardCustomField $customField)
    {
        abort_unless($request->user()?->can('manage_boards'), 403);
        $customField->delete();

        return response()->noContent();
    }

    /** @return array<string, mixed> */
    private function validatePayload(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'label' => [$required, 'string', 'max:120'],
            'type' => [$required, 'string', 'in:'.implode(',', BoardCustomField::TYPES)],
            'options' => ['nullable', 'array', 'max:100'],
            'options.*' => ['string', 'max:120'],
            'required' => ['sometimes', 'boolean'],
            'position' => ['sometimes', 'numeric'],
        ]);
    }

    /** @param  array<string,mixed>  $data */
    private function normaliseOptions(array $data): ?array
    {
        $type = $data['type'] ?? null;
        if (! in_array($type, ['select', 'multi_select'], true)) {
            return null;
        }
        $opts = collect($data['options'] ?? [])
            ->map(fn ($s) => trim((string) $s))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $opts ?: null;
    }

    private function uniqueKey(Board $board, string $label): string
    {
        $base = Str::slug($label, '_') ?: 'field';
        $key = $base;
        $i = 2;
        while (BoardCustomField::where('board_id', $board->id)->where('key', $key)->exists()) {
            $key = $base.'_'.$i;
            $i++;
        }

        return Str::limit($key, 60, '');
    }
}
