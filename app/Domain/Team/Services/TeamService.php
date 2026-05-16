<?php

declare(strict_types=1);

namespace App\Domain\Team\Services;

use App\Domain\Notification\Services\NotificationService;
use App\Domain\Shared\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TeamService
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * List team members for the current tenant.
     *
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return User::query()
            ->when(
                isset($filters['search']),
                fn ($q) => $q->where(function ($q) use ($filters): void {
                    $q->where('name', 'ilike', "%{$filters['search']}%")
                        ->orWhere('email', 'ilike', "%{$filters['search']}%");
                })
            )
            ->when(
                isset($filters['role']),
                fn ($q) => $q->where('role', $filters['role'])
            )
            ->when(
                isset($filters['is_active']),
                fn ($q) => $q->where('is_active', $filters['is_active'])
            )
            ->orderBy('name')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Invite a new team member.
     *
     * @throws ValidationException
     */
    public function invite(string $email, string $name, string $role = 'accountant'): User
    {
        $tenantId = (int) app('tenant.id');

        $exists = User::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('email', $email)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'email' => [
                    'A user with this email already exists.',
                    'يوجد مستخدم بهذا البريد الإلكتروني بالفعل.',
                ],
            ]);
        }

        $userRole = UserRole::tryFrom($role) ?? UserRole::Accountant;

        $user = User::query()->create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'email' => $email,
            'password' => Hash::make(Str::random(16)),
            'role' => $userRole,
            'locale' => 'ar',
            'is_active' => true,
        ]);

        // Assign Spatie role
        try {
            $user->assignRole($userRole->value);
        } catch (\Throwable) {
        }

        $inviterName = Auth::user()?->name ?? 'مدير الحساب';
        $this->notificationService->sendTeamInvite($user->id, $inviterName);

        return $user;
    }

    /**
     * Update a team member's info.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function update(User $user, array $data): User
    {
        if (isset($data['role'])) {
            $role = UserRole::tryFrom($data['role']);

            if (! $role || ! $role->isTenantLevel()) {
                throw ValidationException::withMessages([
                    'role' => [
                        'Invalid role. Must be a tenant-level role.',
                        'دور غير صالح. يجب أن يكون دوراً على مستوى المنشأة.',
                    ],
                ]);
            }

            $data['role'] = $role;
        }

        $user->update($data);

        return $user->refresh();
    }

    /**
     * Toggle a team member's active status.
     *
     * @throws ValidationException
     */
    public function toggleActive(User $user): User
    {
        if ($user->id === Auth::id()) {
            throw ValidationException::withMessages([
                'user' => [
                    'You cannot deactivate your own account.',
                    'لا يمكنك تعطيل حسابك الخاص.',
                ],
            ]);
        }

        $user->update(['is_active' => ! $user->is_active]);

        return $user->refresh();
    }

    /**
     * Remove (soft-delete) a team member.
     *
     * @throws ValidationException
     */
    public function remove(User $user): void
    {
        if ($user->id === Auth::id()) {
            throw ValidationException::withMessages([
                'user' => [
                    'You cannot remove your own account.',
                    'لا يمكنك حذف حسابك الخاص.',
                ],
            ]);
        }

        // Prevent removing the last admin
        $adminCount = User::query()
            ->where('role', UserRole::Admin)
            ->where('is_active', true)
            ->count();

        if ($user->role === UserRole::Admin && $adminCount <= 1) {
            throw ValidationException::withMessages([
                'user' => [
                    'Cannot remove the last admin.',
                    'لا يمكن حذف آخر مدير.',
                ],
            ]);
        }

        $user->delete();
    }
}
