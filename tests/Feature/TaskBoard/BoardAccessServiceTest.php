<?php

declare(strict_types=1);

use App\Domain\TaskBoard\Models\Board;
use App\Domain\TaskBoard\Models\BoardMember;
use App\Domain\TaskBoard\Services\BoardAccessService;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->tenant = createTenant();
    // Spatie caches permissions across tests within a process; clear so
    // permissions we create here are visible to the gate.
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function makeUser(?\App\Domain\Tenant\Models\Tenant $tenant, array $tenantPerms = []): User
{
    $user = createUser(['tenant_id' => $tenant->id]);
    foreach ($tenantPerms as $perm) {
        Permission::findOrCreate($perm, 'web');
        $user->givePermissionTo($perm);
    }
    return $user;
}

function makeBoard(\App\Domain\Tenant\Models\Tenant $tenant, array $attrs = []): Board
{
    return Board::create(array_merge([
        'tenant_id' => $tenant->id,
        'name' => 'Test board',
        'slug' => 'test-board-'.uniqid(),
        'visibility' => 'team',
        'next_task_number' => 1,
    ], $attrs));
}

it('falls back to tenant RBAC when the board has no explicit members', function () {
    $svc = app(BoardAccessService::class);
    $board = makeBoard($this->tenant);

    $viewer = makeUser($this->tenant, ['view_tasks']);
    $editor = makeUser($this->tenant, ['view_tasks', 'edit_tasks']);
    $nobody = makeUser($this->tenant);

    expect($svc->levelFor($board, $viewer))->toBe(BoardMember::LEVEL_VIEWER);
    expect($svc->levelFor($board, $editor))->toBe(BoardMember::LEVEL_EDITOR);
    expect($svc->levelFor($board, $nobody))->toBeNull();
});

it('locks down access once any member row exists', function () {
    $svc = app(BoardAccessService::class);
    $board = makeBoard($this->tenant);
    $member = makeUser($this->tenant, ['view_tasks', 'edit_tasks']);
    $outsider = makeUser($this->tenant, ['view_tasks', 'edit_tasks']);

    BoardMember::create([
        'tenant_id' => $this->tenant->id,
        'board_id' => $board->id,
        'user_id' => $member->id,
        'level' => BoardMember::LEVEL_VIEWER,
    ]);

    expect($svc->levelFor($board, $member))->toBe(BoardMember::LEVEL_VIEWER);
    // Outsider had editor on the tenant — managed board blocks them anyway.
    expect($svc->levelFor($board, $outsider))->toBeNull();
});

it('grants admin to tenant manage_boards holders regardless of membership', function () {
    $svc = app(BoardAccessService::class);
    $board = makeBoard($this->tenant);
    $owner = makeUser($this->tenant, ['manage_boards']);

    // Even with another explicit member locking the board, the workspace
    // admin keeps full access.
    BoardMember::create([
        'tenant_id' => $this->tenant->id,
        'board_id' => $board->id,
        'user_id' => makeUser($this->tenant)->id,
        'level' => BoardMember::LEVEL_VIEWER,
    ]);

    expect($svc->levelFor($board, $owner))->toBe(BoardMember::LEVEL_ADMIN);
});

it('compares levels via the ordered ladder', function () {
    $svc = app(BoardAccessService::class);
    $board = makeBoard($this->tenant);
    $editor = makeUser($this->tenant);

    BoardMember::create([
        'tenant_id' => $this->tenant->id,
        'board_id' => $board->id,
        'user_id' => $editor->id,
        'level' => BoardMember::LEVEL_EDITOR,
    ]);

    expect($svc->has($board, $editor, BoardMember::LEVEL_VIEWER))->toBeTrue();
    expect($svc->has($board, $editor, BoardMember::LEVEL_EDITOR))->toBeTrue();
    expect($svc->has($board, $editor, BoardMember::LEVEL_ADMIN))->toBeFalse();
});

it('accessibleBoardIds returns explicit memberships plus unmanaged boards', function () {
    $svc = app(BoardAccessService::class);

    $unmanaged = makeBoard($this->tenant, ['name' => 'open']);
    $managed   = makeBoard($this->tenant, ['name' => 'locked']);
    $hidden    = makeBoard($this->tenant, ['name' => 'hidden']);

    $user = makeUser($this->tenant, ['view_tasks']);

    // user is a member of `managed` and `hidden` is also managed but has
    // a different user — they should see managed but not hidden.
    BoardMember::create([
        'tenant_id' => $this->tenant->id,
        'board_id' => $managed->id,
        'user_id' => $user->id,
        'level' => BoardMember::LEVEL_VIEWER,
    ]);
    BoardMember::create([
        'tenant_id' => $this->tenant->id,
        'board_id' => $hidden->id,
        'user_id' => makeUser($this->tenant)->id,
        'level' => BoardMember::LEVEL_ADMIN,
    ]);

    $ids = $svc->accessibleBoardIds($user);

    expect($ids)->toContain($unmanaged->id);  // unmanaged + tenant view perm
    expect($ids)->toContain($managed->id);    // explicit member
    expect($ids)->not->toContain($hidden->id); // managed, not a member
});
