<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Events\TaskUpdated as TaskUpdatedEvent;
use App\Domain\TaskBoard\Models\BoardCustomField;
use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskCustomFieldValue;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Bulk-upsert custom field values for one task. The SPA sends a flat map
 * { custom_field_id: value } where `value` is the typed JSON shape for
 * that field. We validate per-field-type and upsert into
 * task_custom_field_values, then dispatch a TaskUpdated event so
 * realtime + activity reflect the change.
 */
class TaskCustomFieldValueController extends Controller
{
    public function upsert(Request $request, Task $task): JsonResponse
    {
        abort_unless($request->user()?->can('edit_tasks'), 403);

        $payload = $request->validate([
            'values' => ['required', 'array'],
            'values.*' => ['nullable'],
        ])['values'];

        // Load all fields for this board so we know which keys are legal
        // and what their types are.
        $fields = BoardCustomField::query()
            ->where('board_id', $task->board_id)
            ->get()
            ->keyBy('id');

        $changed = [];
        foreach ($payload as $rawId => $value) {
            $fieldId = (int) $rawId;
            $field = $fields[$fieldId] ?? null;
            if (! $field) {
                continue; // Silently ignore fields that don't belong to this board.
            }

            $typed = $this->coerce($value, $field);

            if ($typed === null) {
                // Delete the row outright if value cleared — keeps the
                // table sparse so reads don't return a sea of nulls.
                TaskCustomFieldValue::where('task_id', $task->id)
                    ->where('custom_field_id', $fieldId)
                    ->delete();
            } else {
                TaskCustomFieldValue::updateOrCreate(
                    ['task_id' => $task->id, 'custom_field_id' => $fieldId],
                    ['value' => $typed],
                );
            }
            $changed[] = $fieldId;
        }

        if ($changed) {
            TaskUpdatedEvent::dispatch(
                $task->fresh() ?? $task,
                array_map(fn ($id) => "custom_field:$id", $changed),
                $request->user()?->id,
            );
        }

        $values = TaskCustomFieldValue::where('task_id', $task->id)->get();

        return response()->json([
            'data' => $values->mapWithKeys(fn ($v) => [(string) $v->custom_field_id => $v->value]),
        ]);
    }

    /**
     * Coerce a raw input value into the canonical shape for the field
     * type. Returns null to signal "no value" (caller deletes the row).
     */
    private function coerce(mixed $value, BoardCustomField $field): mixed
    {
        $isBlank = $value === null || $value === '' || $value === [];
        if ($isBlank && $field->type !== 'checkbox') {
            return null;
        }

        $rules = match ($field->type) {
            'text', 'url' => ['value' => ['string', 'max:2000']],
            'number' => ['value' => ['numeric']],
            'date' => ['value' => ['date']],
            'checkbox' => ['value' => ['boolean']],
            'select' => ['value' => ['string', 'max:120']],
            'multi_select' => [
                'value' => ['array'],
                'value.*' => ['string', 'max:120'],
            ],
            default => ['value' => []],
        };

        $validator = Validator::make(['value' => $value], $rules);
        if ($validator->fails()) {
            abort(422, "Invalid value for {$field->key}: ".$validator->errors()->first());
        }

        // For select/multi_select, restrict to declared options.
        if (in_array($field->type, ['select', 'multi_select'], true)) {
            $allowed = (array) $field->options;
            if ($field->type === 'select') {
                if (! in_array($value, $allowed, true)) {
                    abort(422, "Value not in allowed options for {$field->key}.");
                }
            } else {
                $vals = array_values(array_unique((array) $value));
                foreach ($vals as $v) {
                    if (! in_array($v, $allowed, true)) {
                        abort(422, "Value not in allowed options for {$field->key}.");
                    }
                }
                return $vals;
            }
        }

        // Normalise date to ISO 'YYYY-MM-DD' for stable comparisons.
        if ($field->type === 'date') {
            return \Carbon\Carbon::parse((string) $value)->toDateString();
        }

        // Number → cast to float (Postgres jsonb stores numerics natively).
        if ($field->type === 'number') {
            return (float) $value;
        }

        // Checkbox is boolean and treats blank as false (which is a valid stored value).
        if ($field->type === 'checkbox') {
            return (bool) $value;
        }

        return $value;
    }
}
