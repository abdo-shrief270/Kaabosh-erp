<?php

declare(strict_types=1);

use App\Domain\TaskBoard\Models\Board;
use App\Domain\TaskBoard\Models\BoardColumn;
use App\Domain\TaskBoard\Models\BoardMember;
use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskApprovalRequest;
use App\Domain\TaskBoard\Models\TaskType;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->tenant = createTenant();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    foreach (['view_tasks', 'create_tasks', 'edit_tasks', 'manage_boards'] as $p) {
        Permission::findOrCreate($p, 'web');
    }

    $this->editor = createUser(['tenant_id' => $this->tenant->id]);
    $this->editor->givePermissionTo(['view_tasks', 'edit_tasks']);

    $this->admin = createUser(['tenant_id' => $this->tenant->id]);
    $this->admin->givePermissionTo(['view_tasks', 'edit_tasks', 'manage_boards']);

    $this->board = Board::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Eng', 'slug' => 'eng-'.uniqid(),
        'visibility' => 'team', 'next_task_number' => 1, 'key' => 'ENG',
    ]);
    $this->todo = BoardColumn::create(['tenant_id' => $this->tenant->id, 'board_id' => $this->board->id, 'name' => 'Todo', 'position' => 1000, 'is_initial' => true]);
    // Done column requires approval.
    $this->done = BoardColumn::create([
        'tenant_id' => $this->tenant->id, 'board_id' => $this->board->id,
        'name' => 'Done', 'position' => 2000, 'is_done' => true,
        'requires_approval' => true,
    ]);
    $this->type = TaskType::create(['tenant_id' => $this->tenant->id, 'name' => 'Story', 'slug' => 'story']);
});

function makeApprovalTask(BoardColumn $c, int $tenantId, int $reporterId, int $typeId): Task
{
    return Task::create([
        'tenant_id' => $tenantId,
        'board_id' => $c->board_id,
        'board_column_id' => $c->id,
        'task_type_id' => $typeId,
        'title' => 'Ready to ship',
        'priority' => 'medium',
        'reporter_id' => $reporterId,
        'position' => 1000,
    ]);
}

it('blocks direct moves into an approval-gated column', function () {
    actingAsUser($this->editor);
    $this->be($this->editor);

    $task = makeApprovalTask($this->todo, $this->tenant->id, $this->editor->id, $this->type->id);

    $res = $this->withHeader('X-Tenant', $this->tenant->slug)
        ->postJson("/api/v1/tasks/{$task->id}/move", ['board_column_id' => $this->done->id]);

    $res->assertStatus(422);
    expect($res->json('error'))->toBe('approval_required');
    expect($task->fresh()->board_column_id)->toBe($this->todo->id);
});

it('creates a pending approval request that admins can approve, completing the move', function () {
    actingAsUser($this->editor);
    $this->be($this->editor);

    $task = makeApprovalTask($this->todo, $this->tenant->id, $this->editor->id, $this->type->id);

    // Editor requests approval.
    $reqRes = $this->withHeader('X-Tenant', $this->tenant->slug)
        ->postJson("/api/v1/tasks/{$task->id}/approval-requests", [
            'target_column_id' => $this->done->id,
        ]);
    $reqRes->assertCreated();
    $requestId = $reqRes->json('data.id');
    expect(TaskApprovalRequest::find($requestId)->status)->toBe('pending');

    // Admin approves.
    actingAsUser($this->admin);
    $this->be($this->admin);
    $decRes = $this->withHeader('X-Tenant', $this->tenant->slug)
        ->postJson("/api/v1/task-approval-requests/{$requestId}/decision", [
            'status' => 'approved',
        ]);
    $decRes->assertOk();

    expect(TaskApprovalRequest::find($requestId)->status)->toBe('approved');
    expect($task->fresh()->board_column_id)->toBe($this->done->id);
});

it('rejected requests leave the task where it was', function () {
    actingAsUser($this->editor);
    $this->be($this->editor);

    $task = makeApprovalTask($this->todo, $this->tenant->id, $this->editor->id, $this->type->id);

    $reqRes = $this->withHeader('X-Tenant', $this->tenant->slug)
        ->postJson("/api/v1/tasks/{$task->id}/approval-requests", [
            'target_column_id' => $this->done->id,
        ]);
    $requestId = $reqRes->json('data.id');

    actingAsUser($this->admin);
    $this->be($this->admin);
    $this->withHeader('X-Tenant', $this->tenant->slug)
        ->postJson("/api/v1/task-approval-requests/{$requestId}/decision", [
            'status' => 'rejected',
            'reason' => 'Not yet — please attach the test report.',
        ])
        ->assertOk();

    expect(TaskApprovalRequest::find($requestId)->status)->toBe('rejected');
    expect($task->fresh()->board_column_id)->toBe($this->todo->id);
});

it('refuses a second pending request for the same task', function () {
    actingAsUser($this->editor);
    $this->be($this->editor);
    $task = makeApprovalTask($this->todo, $this->tenant->id, $this->editor->id, $this->type->id);

    $this->withHeader('X-Tenant', $this->tenant->slug)
        ->postJson("/api/v1/tasks/{$task->id}/approval-requests", ['target_column_id' => $this->done->id])
        ->assertCreated();

    $this->withHeader('X-Tenant', $this->tenant->slug)
        ->postJson("/api/v1/tasks/{$task->id}/approval-requests", ['target_column_id' => $this->done->id])
        ->assertStatus(409);
});

it('explicit column approvers can decide even without board admin', function () {
    // Promote editor to approver for the Done column.
    $this->done->forceFill(['approver_user_ids' => [$this->editor->id]])->save();

    $other = createUser(['tenant_id' => $this->tenant->id]);
    $other->givePermissionTo(['view_tasks', 'edit_tasks']);
    actingAsUser($other);
    $this->be($other);

    $task = makeApprovalTask($this->todo, $this->tenant->id, $other->id, $this->type->id);
    $reqId = $this->withHeader('X-Tenant', $this->tenant->slug)
        ->postJson("/api/v1/tasks/{$task->id}/approval-requests", ['target_column_id' => $this->done->id])
        ->json('data.id');

    actingAsUser($this->editor);
    $this->be($this->editor);
    $this->withHeader('X-Tenant', $this->tenant->slug)
        ->postJson("/api/v1/task-approval-requests/{$reqId}/decision", ['status' => 'approved'])
        ->assertOk();

    expect($task->fresh()->board_column_id)->toBe($this->done->id);
});

it('non-admins cannot decide a request', function () {
    actingAsUser($this->editor);
    $this->be($this->editor);
    $task = makeApprovalTask($this->todo, $this->tenant->id, $this->editor->id, $this->type->id);
    $reqId = $this->withHeader('X-Tenant', $this->tenant->slug)
        ->postJson("/api/v1/tasks/{$task->id}/approval-requests", ['target_column_id' => $this->done->id])
        ->json('data.id');

    // Same editor (non-admin) tries to approve their own request.
    $this->withHeader('X-Tenant', $this->tenant->slug)
        ->postJson("/api/v1/task-approval-requests/{$reqId}/decision", ['status' => 'approved'])
        ->assertStatus(403);

    expect(TaskApprovalRequest::find($reqId)->status)->toBe('pending');
});
