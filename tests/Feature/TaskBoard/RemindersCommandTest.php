<?php

declare(strict_types=1);

use App\Domain\Notification\Models\Notification;
use App\Domain\TaskBoard\Models\Board;
use App\Domain\TaskBoard\Models\BoardColumn;
use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskReminder;
use App\Domain\TaskBoard\Models\TaskType;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->tenant = createTenant();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    foreach (['view_tasks'] as $p) Permission::findOrCreate($p, 'web');

    $this->user = createUser(['tenant_id' => $this->tenant->id]);
    $this->board = Board::create([
        'tenant_id' => $this->tenant->id, 'name' => 'Eng',
        'slug' => 'eng-'.uniqid(), 'visibility' => 'team',
        'next_task_number' => 1, 'key' => 'ENG',
    ]);
    $this->column = BoardColumn::create([
        'tenant_id' => $this->tenant->id, 'board_id' => $this->board->id,
        'name' => 'Todo', 'position' => 1000, 'is_initial' => true,
    ]);
    $this->type = TaskType::create(['tenant_id' => $this->tenant->id, 'name' => 'S', 'slug' => 's']);
    $this->task = Task::create([
        'tenant_id' => $this->tenant->id, 'board_id' => $this->board->id,
        'board_column_id' => $this->column->id, 'task_type_id' => $this->type->id,
        'title' => 'Ship it', 'priority' => 'medium',
        'reporter_id' => $this->user->id, 'position' => 1000,
    ]);
});

it('fires due reminders into the notification stream and marks them sent', function () {
    $due = TaskReminder::create([
        'tenant_id' => $this->tenant->id,
        'task_id' => $this->task->id,
        'user_id' => $this->user->id,
        'remind_at' => now()->subMinute(),
        'note' => 'Don\'t forget',
    ]);
    $future = TaskReminder::create([
        'tenant_id' => $this->tenant->id,
        'task_id' => $this->task->id,
        'user_id' => $this->user->id,
        'remind_at' => now()->addHour(),
    ]);

    $this->artisan('task-board:fire-reminders')->assertSuccessful();

    expect($due->fresh()->sent_at)->not->toBeNull();
    expect($future->fresh()->sent_at)->toBeNull();

    // A Notification row should land for the user.
    expect(Notification::where('user_id', $this->user->id)->where('type', 'task_reminder')->count())
        ->toBeGreaterThan(0);
});

it('is idempotent — re-running does not re-send sent reminders', function () {
    $due = TaskReminder::create([
        'tenant_id' => $this->tenant->id,
        'task_id' => $this->task->id,
        'user_id' => $this->user->id,
        'remind_at' => now()->subMinute(),
    ]);

    $this->artisan('task-board:fire-reminders')->assertSuccessful();
    $countAfterFirst = Notification::where('user_id', $this->user->id)->count();

    $this->artisan('task-board:fire-reminders')->assertSuccessful();
    $countAfterSecond = Notification::where('user_id', $this->user->id)->count();

    expect($countAfterSecond)->toBe($countAfterFirst);
    expect($due->fresh()->sent_at)->not->toBeNull();
});
