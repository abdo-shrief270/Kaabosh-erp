<?php

declare(strict_types=1);

namespace App\Domain\Admin\Services;

use App\Domain\Shared\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminUserService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return User::withoutGlobalScopes()
            ->with('tenant')
            ->when(isset($filters['role']), fn ($q) => $q->where('role', $filters['role']))
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->when(
                isset($filters['search']),
                fn ($q) => $q->where(function ($q) use ($filters): void {
                    $q->where('name', 'ilike', "%{$filters['search']}%")
                        ->orWhere('email', 'ilike', "%{$filters['search']}%");
                })
            )
            ->orderByDesc('created_at')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
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
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function createSuperAdmin(array $data): User
    {
        $exists = User::withoutGlobalScopes()
            ->where('email', $data['email'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'email' => [
                    'A user with this email already exists.',
                    'يوجد مستخدم بهذا البريد الإلكتروني بالفعل.',
                ],
            ]);
        }

        return User::withoutGlobalScopes()->create([
            'tenant_id' => null,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => UserRole::SuperAdmin,
            'locale' => 'ar',
            'is_active' => true,
        ]);
    }
}
