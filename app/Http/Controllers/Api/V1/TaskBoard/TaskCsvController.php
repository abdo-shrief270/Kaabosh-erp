<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Events\TaskCreated as TaskCreatedEvent;
use App\Domain\TaskBoard\Models\Board;
use App\Domain\TaskBoard\Models\BoardColumn;
use App\Domain\TaskBoard\Models\Tag;
use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskType;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV import/export for a single board's tasks. Export streams the rows so a
 * 50k-task board doesn't balloon memory; import is bounded to 5k rows per
 * request and runs inside a single transaction so a row-level failure rolls
 * everything back rather than leaving the board half-imported.
 */
class TaskCsvController extends Controller
{
    private const IMPORT_ROW_CAP = 5000;
    private const EXPORT_COLUMNS = [
        'reference', 'title', 'description', 'status', 'priority',
        'assignee_email', 'reporter_email', 'type', 'tags',
        'start_date', 'due_date', 'estimate_hours', 'progress',
        'parent_reference', 'completed_at', 'created_at',
    ];

    public function export(Request $request, Board $board): StreamedResponse
    {
        abort_unless($request->user()?->can('view_tasks'), 403);

        $filename = sprintf(
            '%s-tasks-%s.csv',
            Str::slug($board->name) ?: 'board',
            now()->format('Ymd-His'),
        );

        return response()->streamDownload(function () use ($board) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel detects UTF-8
            fputcsv($out, self::EXPORT_COLUMNS);

            Task::query()
                ->where('board_id', $board->id)
                ->with([
                    'column:id,name',
                    'type:id,name',
                    'primaryAssignee:id,email',
                    'reporter:id,email',
                    'tags:id,name',
                    'parent:id,reference',
                ])
                ->orderBy('id')
                ->chunk(500, function ($tasks) use ($out) {
                    foreach ($tasks as $t) {
                        fputcsv($out, [
                            $t->reference,
                            $t->title,
                            (string) ($t->description ?? ''),
                            $t->column?->name ?? '',
                            (string) ($t->priority?->value ?? $t->priority ?? ''),
                            $t->primaryAssignee?->email ?? '',
                            $t->reporter?->email ?? '',
                            $t->type?->name ?? '',
                            $t->tags->pluck('name')->implode('|'),
                            optional($t->start_date)->toDateString() ?? '',
                            optional($t->due_date)->toDateString() ?? '',
                            $t->estimate_hours !== null ? (string) $t->estimate_hours : '',
                            $t->progress !== null ? (string) $t->progress : '',
                            $t->parent?->reference ?? '',
                            optional($t->completed_at)->toIso8601String() ?? '',
                            optional($t->created_at)->toIso8601String() ?? '',
                        ]);
                    }
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function import(Request $request, Board $board): JsonResponse
    {
        abort_unless($request->user()?->can('create_tasks'), 403);

        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'default_column_id' => ['nullable', 'integer', 'exists:board_columns,id,tenant_id,'.app('tenant.id').',board_id,'.$board->id],
        ]);

        $defaultColumnId = $data['default_column_id']
            ?? BoardColumn::where('board_id', $board->id)->where('is_initial', true)->value('id')
            ?? BoardColumn::where('board_id', $board->id)->orderBy('position')->value('id');

        abort_if(! $defaultColumnId, 422, 'Board has no columns to import into.');

        $path = $request->file('file')->getRealPath();
        $rows = $this->readCsv($path);

        if (! $rows) {
            return response()->json(['created' => 0, 'errors' => ['File is empty.']]);
        }

        $headers = array_map(
            fn ($h) => Str::snake(Str::lower(trim((string) $h))),
            array_shift($rows) ?? [],
        );

        if (count($rows) > self::IMPORT_ROW_CAP) {
            abort(422, 'Too many rows. Cap is '.self::IMPORT_ROW_CAP.'.');
        }

        $columnsByName = BoardColumn::where('board_id', $board->id)
            ->pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [Str::lower((string) $name) => $id]);
        $typesByName = TaskType::query()
            ->where('tenant_id', app('tenant.id'))
            ->pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [Str::lower((string) $name) => $id]);
        // tasks.task_type_id is NOT NULL — pick a deterministic default so
        // CSVs that omit `type` (or name a type that doesn't exist yet)
        // still import cleanly. First non-subtask type, falling back to
        // the first row if every type is a subtask kind.
        $defaultTypeId = (int) (TaskType::query()
            ->where('tenant_id', app('tenant.id'))
            ->where('is_subtask', false)
            ->orderBy('id')
            ->value('id')
            ?? TaskType::query()
                ->where('tenant_id', app('tenant.id'))
                ->orderBy('id')
                ->value('id'));
        abort_if(! $defaultTypeId, 422, 'Create at least one task type before importing.');
        $usersByEmail = User::query()
            ->where('tenant_id', app('tenant.id'))
            ->pluck('id', 'email')
            ->mapWithKeys(fn ($id, $email) => [Str::lower((string) $email) => $id]);

        $tagCache = [];
        $resolveTag = function (string $name) use (&$tagCache, $board): int {
            $key = Str::lower(trim($name));
            if (isset($tagCache[$key])) return $tagCache[$key];
            $tag = Tag::firstOrCreate(
                ['tenant_id' => app('tenant.id'), 'board_id' => $board->id, 'slug' => Str::slug($name)],
                ['name' => trim($name), 'color' => '#94a3b8'],
            );
            return $tagCache[$key] = (int) $tag->id;
        };

        $referenceToId = [];
        $created = [];
        $errors = [];
        $pendingParents = [];

        DB::transaction(function () use (
            &$created, &$errors, &$referenceToId, &$pendingParents,
            $rows, $headers, $board, $defaultColumnId, $defaultTypeId,
            $columnsByName, $typesByName, $usersByEmail, $resolveTag, $request,
        ) {
            $position = (float) (Task::where('board_column_id', $defaultColumnId)->max('position') ?? 0);

            foreach ($rows as $i => $row) {
                $rowNum = $i + 2; // 1 for header, 1 for 1-indexed
                if (! array_filter($row, fn ($v) => trim((string) $v) !== '')) continue; // skip blank

                $data = array_combine(
                    $headers + array_keys($row),
                    array_pad($row, count($headers), null),
                );
                $data = array_intersect_key($data, array_flip($headers));

                $title = trim((string) ($data['title'] ?? ''));
                if ($title === '') {
                    $errors[] = "Row $rowNum: missing title.";
                    continue;
                }

                $columnName = Str::lower(trim((string) ($data['status'] ?? '')));
                $columnId = $columnName && $columnsByName->has($columnName)
                    ? (int) $columnsByName[$columnName]
                    : (int) $defaultColumnId;

                $priority = Str::lower(trim((string) ($data['priority'] ?? 'medium'))) ?: 'medium';
                if (! in_array($priority, ['low', 'medium', 'high', 'critical'], true)) {
                    $priority = 'medium';
                }

                $typeName = Str::lower(trim((string) ($data['type'] ?? '')));
                $typeId = $typeName && $typesByName->has($typeName)
                    ? (int) $typesByName[$typeName]
                    : $defaultTypeId;

                $assigneeEmail = Str::lower(trim((string) ($data['assignee_email'] ?? '')));
                $assigneeId = $assigneeEmail && $usersByEmail->has($assigneeEmail) ? (int) $usersByEmail[$assigneeEmail] : null;

                $reporterEmail = Str::lower(trim((string) ($data['reporter_email'] ?? '')));
                $reporterId = $reporterEmail && $usersByEmail->has($reporterEmail)
                    ? (int) $usersByEmail[$reporterEmail]
                    : (int) $request->user()->id;

                $position += 1000.0;
                $task = Task::create([
                    'tenant_id' => app('tenant.id'),
                    'board_id' => $board->id,
                    'board_column_id' => $columnId,
                    'task_type_id' => $typeId,
                    'title' => $title,
                    'description' => (string) ($data['description'] ?? '') ?: null,
                    'priority' => $priority,
                    'reporter_id' => $reporterId,
                    'primary_assignee_id' => $assigneeId,
                    'start_date' => $this->parseDate($data['start_date'] ?? null),
                    'due_date' => $this->parseDate($data['due_date'] ?? null),
                    'estimate_hours' => is_numeric($data['estimate_hours'] ?? null)
                        ? (float) $data['estimate_hours'] : null,
                    'progress' => is_numeric($data['progress'] ?? null)
                        ? (int) max(0, min(100, (int) $data['progress'])) : 0,
                    'position' => $position,
                ]);

                if ($assigneeId) {
                    $task->assignees()->sync([
                        $assigneeId => ['assigned_by_id' => $request->user()->id, 'assigned_at' => now()],
                    ]);
                }

                $tagsRaw = trim((string) ($data['tags'] ?? ''));
                if ($tagsRaw !== '') {
                    $tagIds = collect(preg_split('/[|,]/', $tagsRaw))
                        ->map(fn ($n) => trim((string) $n))
                        ->filter()
                        ->map($resolveTag)
                        ->unique()
                        ->values()
                        ->all();
                    if ($tagIds) $task->tags()->sync($tagIds);
                }

                $created[] = $task;
                if (! empty($task->reference)) {
                    $referenceToId[$task->reference] = $task->id;
                }

                $parentRef = trim((string) ($data['parent_reference'] ?? ''));
                if ($parentRef !== '') {
                    $pendingParents[$task->id] = $parentRef;
                }
            }

            // Resolve parent links in a second pass so forward references work.
            foreach ($pendingParents as $childId => $parentRef) {
                $parentId = $referenceToId[$parentRef]
                    ?? Task::where('board_id', $board->id)
                        ->where('reference', $parentRef)
                        ->value('id');
                if ($parentId && $parentId !== $childId) {
                    Task::where('id', $childId)->update(['parent_task_id' => $parentId]);
                }
            }
        });

        foreach ($created as $task) {
            TaskCreatedEvent::dispatch($task, $request->user()?->id);
        }

        return response()->json([
            'created' => count($created),
            'errors' => $errors,
        ]);
    }

    /**
     * Read a CSV file into an array of rows. Detects common delimiters and
     * trims a UTF-8 BOM if present.
     */
    private function readCsv(string $path): array
    {
        $contents = file_get_contents($path);
        if ($contents === false) return [];

        // Strip BOM
        if (Str::startsWith($contents, "\xEF\xBB\xBF")) {
            $contents = substr($contents, 3);
        }

        $tmp = fopen('php://temp', 'r+');
        fwrite($tmp, $contents);
        rewind($tmp);

        // Sniff delimiter from first line — commas vs semicolons (Excel/EU)
        $first = fgets($tmp);
        rewind($tmp);
        $delim = (substr_count((string) $first, ';') > substr_count((string) $first, ',')) ? ';' : ',';

        $rows = [];
        while (($row = fgetcsv($tmp, 0, $delim)) !== false) {
            $rows[] = $row;
        }
        fclose($tmp);

        return $rows;
    }

    private function parseDate($value): ?string
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') return null;
        try {
            return \Carbon\Carbon::parse($value)->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }
}
