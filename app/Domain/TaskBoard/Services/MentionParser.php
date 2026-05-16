<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Services;

use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Pulls user and role mentions out of comment markdown.
 *
 * Frontend writes mentions in two canonical shapes (same convention as
 * Linear/Notion):
 *   - "@[Name](user:123)"      — single user
 *   - "@[Role Name](role:42)"  — expanded to every user with that role
 *
 * Roles are tenant-scoped via spatie/laravel-permission's `team_id`
 * column. The parser returns:
 *   - `users`: deduped, validated user ids inside the tenant
 *   - `roles`: role ids that were named (kept so the UI can render them
 *     as chips and so we can store them per-comment if needed)
 *   - `expanded_users`: the union of `users` + every user belonging to
 *     a mentioned role, deduped — this is what the notification
 *     dispatcher fans out on.
 */
class MentionParser
{
    private const USER_PATTERN = '/@\[([^\]]+)\]\(user:(\d+)\)/';
    private const ROLE_PATTERN = '/@\[([^\]]+)\]\(role:(\d+)\)/';

    /**
     * @return int[]  (deprecated legacy shape; returns expanded_users for
     *                 backward compatibility with callers that only stored
     *                 a flat list).
     */
    public function extract(string $body, int $tenantId): array
    {
        return $this->parse($body, $tenantId)['expanded_users'];
    }

    /**
     * Full mention breakdown.
     *
     * @return array{users: int[], roles: int[], expanded_users: int[]}
     */
    public function parse(string $body, int $tenantId): array
    {
        $userIds = $this->extractUserIds($body, $tenantId);
        $roleIds = $this->extractRoleIds($body, $tenantId);
        $expanded = $userIds;

        if ($roleIds) {
            $roleUsers = User::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereHas('roles', fn ($q) => $q->whereIn('id', $roleIds))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $expanded = array_values(array_unique(array_merge($expanded, $roleUsers)));
        }

        return [
            'users' => $userIds,
            'roles' => $roleIds,
            'expanded_users' => $expanded,
        ];
    }

    /** @return int[] */
    private function extractUserIds(string $body, int $tenantId): array
    {
        if (! preg_match_all(self::USER_PATTERN, $body, $matches)) {
            return [];
        }

        $ids = collect($matches[2])->map(fn ($id) => (int) $id)->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        return User::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $ids->all())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** @return int[] */
    private function extractRoleIds(string $body, int $tenantId): array
    {
        if (! preg_match_all(self::ROLE_PATTERN, $body, $matches)) {
            return [];
        }

        $ids = collect($matches[2])->map(fn ($id) => (int) $id)->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        $query = Role::query()->whereIn('id', $ids->all());
        // spatie/laravel-permission can be configured with teams enabled;
        // when it is, roles carry a `team_id` we treat as tenant. If the
        // schema doesn't have that column the where() is a no-op.
        if (\Illuminate\Support\Facades\Schema::hasColumn('roles', 'team_id')) {
            $query->where('team_id', $tenantId);
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
}
