<?php

declare(strict_types=1);

use App\Domain\TaskBoard\Models\Board;
use App\Domain\TaskBoard\Models\BoardColumn;
use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskType;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->tenant = createTenant();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    foreach (['view_tasks'] as $p) {
        Permission::findOrCreate($p, 'web');
    }
    $this->user = createUser(['tenant_id' => $this->tenant->id]);
    $this->user->givePermissionTo('view_tasks');

    $this->board = Board::create([
        'tenant_id' => $this->tenant->id, 'name' => 'Eng',
        'slug' => 'eng-'.uniqid(), 'visibility' => 'team',
        'next_task_number' => 1, 'key' => 'ENG',
    ]);
    $this->column = BoardColumn::create([
        'tenant_id' => $this->tenant->id, 'board_id' => $this->board->id,
        'name' => 'Todo', 'position' => 1000, 'is_initial' => true,
    ]);
    $this->type = TaskType::create(['tenant_id' => $this->tenant->id, 'name' => 'Story', 'slug' => 'story']);
});

function makeICalTask(int $tenantId, int $boardId, int $columnId, int $typeId, int $assigneeId, ?string $due): Task
{
    return Task::create([
        'tenant_id' => $tenantId,
        'board_id' => $boardId,
        'board_column_id' => $columnId,
        'task_type_id' => $typeId,
        'title' => 'Ship the thing',
        'priority' => 'medium',
        'reporter_id' => $assigneeId,
        'primary_assignee_id' => $assigneeId,
        'position' => 1000,
        'due_date' => $due,
    ]);
}

it('issues a token on first request and reuses it on the second', function () {
    actingAsUser($this->user);
    $this->be($this->user);

    $first = $this->withHeader('X-Tenant', $this->tenant->slug)
        ->getJson('/api/v1/me/ical/token')->json();
    $second = $this->withHeader('X-Tenant', $this->tenant->slug)
        ->getJson('/api/v1/me/ical/token')->json();

    expect($first['token'])->toBe($second['token'])
        ->and($first['url'])->toContain($first['token']);
});

it('rotate replaces the token and the old one stops working', function () {
    actingAsUser($this->user);
    $this->be($this->user);

    $issued = $this->withHeader('X-Tenant', $this->tenant->slug)
        ->getJson('/api/v1/me/ical/token')->json();
    $rotated = $this->withHeader('X-Tenant', $this->tenant->slug)
        ->postJson('/api/v1/me/ical/token/rotate')->json();

    expect($issued['token'])->not->toBe($rotated['token']);

    $this->get("/api/v1/ical/tasks/{$issued['token']}.ics")
        ->assertStatus(404);
    $this->get("/api/v1/ical/tasks/{$rotated['token']}.ics")
        ->assertOk()
        ->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
});

it('emits a VEVENT per assigned/watched task with a due_date', function () {
    actingAsUser($this->user);
    $this->be($this->user);

    $withDue = makeICalTask($this->tenant->id, $this->board->id, $this->column->id, $this->type->id, $this->user->id, '2026-06-15 10:00:00');
    $noDue   = makeICalTask($this->tenant->id, $this->board->id, $this->column->id, $this->type->id, $this->user->id, null);

    $token = $this->withHeader('X-Tenant', $this->tenant->slug)
        ->getJson('/api/v1/me/ical/token')->json('token');

    $body = $this->get("/api/v1/ical/tasks/{$token}.ics")->getContent();

    expect($body)->toContain('BEGIN:VCALENDAR')
        ->and($body)->toContain('END:VCALENDAR')
        ->and($body)->toContain('SUMMARY:[ENG-'.$withDue->number.']')
        // The dateless task must not appear as a VEVENT.
        ->and(substr_count($body, 'BEGIN:VEVENT'))->toBe(1);
});

it('escapes RFC 5545 special characters in TEXT values', function () {
    actingAsUser($this->user);
    $this->be($this->user);

    $task = Task::create([
        'tenant_id' => $this->tenant->id,
        'board_id' => $this->board->id,
        'board_column_id' => $this->column->id,
        'task_type_id' => $this->type->id,
        'title' => 'Tricky; title, with\\backslashes',
        'priority' => 'medium',
        'reporter_id' => $this->user->id,
        'primary_assignee_id' => $this->user->id,
        'position' => 1000,
        'due_date' => '2026-06-15 10:00:00',
    ]);

    $token = $this->withHeader('X-Tenant', $this->tenant->slug)
        ->getJson('/api/v1/me/ical/token')->json('token');
    $body = $this->get("/api/v1/ical/tasks/{$token}.ics")->getContent();

    // Semicolons + commas + backslashes must be escaped per RFC 5545 §3.3.11.
    expect($body)->toContain('Tricky\\; title\\, with\\\\backslashes');
});
