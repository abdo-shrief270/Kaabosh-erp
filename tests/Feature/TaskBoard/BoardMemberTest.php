<?php

declare(strict_types=1);

use App\Domain\TaskBoard\Models\Board;
use App\Domain\TaskBoard\Models\BoardMember;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->tenant = createTenant();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    foreach (['view_tasks', 'edit_tasks', 'manage_boards'] as $p) {
        Permission::findOrCreate($p, 'web');
    }

    $this->admin = createUser(['tenant_id' => $this->tenant->id]);
    $this->admin->givePermissionTo(['view_tasks', 'edit_tasks', 'manage_boards']);

    $this->board = Board::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Eng', 'slug' => 'eng-'.uniqid(),
        'visibility' => 'team', 'next_task_number' => 1, 'key' => 'ENG',
    ]);
});

it('promotes the acting admin when they add the first member to an unmanaged board', function () {
    actingAsUser($this->admin);
    $this->be($this->admin);

    $other = createUser(['tenant_id' => $this->tenant->id]);
    $other->givePermissionTo('view_tasks');

    $this->withHeader('X-Tenant', $this->tenant->slug)
        ->postJson("/api/v1/boards/{$this->board->id}/members", [
            'user_id' => $other->id, 'level' => 'editor',
        ])
        ->assertCreated();

    // Two rows: the requested other user + the acting admin auto-promoted.
    $rows = BoardMember::where('board_id', $this->board->id)->get();
    expect($rows->pluck('user_id')->all())->toContain($this->admin->id, $other->id);
    $adminRow = $rows->firstWhere('user_id', $this->admin->id);
    expect($adminRow->level)->toBe('admin');
});

it('refuses to demote the last admin', function () {
    actingAsUser($this->admin);
    $this->be($this->admin);

    // Lock the board: admin self-adds.
    BoardMember::create([
        'tenant_id' => $this->tenant->id,
        'board_id' => $this->board->id,
        'user_id' => $this->admin->id,
        'level' => 'admin',
    ]);
    $adminRow = BoardMember::where(['board_id' => $this->board->id, 'user_id' => $this->admin->id])->first();

    $this->withHeader('X-Tenant', $this->tenant->slug)
        ->putJson("/api/v1/board-members/{$adminRow->id}", ['level' => 'editor'])
        ->assertStatus(422);

    expect($adminRow->fresh()->level)->toBe('admin');
});

it('refuses to delete the last admin', function () {
    actingAsUser($this->admin);
    $this->be($this->admin);

    BoardMember::create([
        'tenant_id' => $this->tenant->id,
        'board_id' => $this->board->id,
        'user_id' => $this->admin->id,
        'level' => 'admin',
    ]);
    $adminRow = BoardMember::where(['board_id' => $this->board->id, 'user_id' => $this->admin->id])->first();

    $this->withHeader('X-Tenant', $this->tenant->slug)
        ->deleteJson("/api/v1/board-members/{$adminRow->id}")
        ->assertStatus(422);

    expect(BoardMember::find($adminRow->id))->not->toBeNull();
});

it('allows demoting an admin when another admin still exists', function () {
    actingAsUser($this->admin);
    $this->be($this->admin);

    BoardMember::create([
        'tenant_id' => $this->tenant->id, 'board_id' => $this->board->id,
        'user_id' => $this->admin->id, 'level' => 'admin',
    ]);
    $second = createUser(['tenant_id' => $this->tenant->id]);
    $secondRow = BoardMember::create([
        'tenant_id' => $this->tenant->id, 'board_id' => $this->board->id,
        'user_id' => $second->id, 'level' => 'admin',
    ]);

    $this->withHeader('X-Tenant', $this->tenant->slug)
        ->putJson("/api/v1/board-members/{$secondRow->id}", ['level' => 'editor'])
        ->assertOk();

    expect($secondRow->fresh()->level)->toBe('editor');
});

it('hides managed boards from outsiders in the listing', function () {
    actingAsUser($this->admin);
    $this->be($this->admin);

    BoardMember::create([
        'tenant_id' => $this->tenant->id, 'board_id' => $this->board->id,
        'user_id' => $this->admin->id, 'level' => 'admin',
    ]);

    $outsider = createUser(['tenant_id' => $this->tenant->id]);
    $outsider->givePermissionTo('view_tasks');

    actingAsUser($outsider);
    $this->be($outsider);
    $res = $this->withHeader('X-Tenant', $this->tenant->slug)
        ->getJson('/api/v1/boards');
    $res->assertOk();
    expect(collect($res->json('data'))->pluck('id'))->not->toContain($this->board->id);
});
