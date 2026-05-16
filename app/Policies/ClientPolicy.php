<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Client\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_clients');
    }

    public function view(User $user, Client $client): bool
    {
        return $user->hasPermissionTo('manage_clients')
            && $user->tenant_id === $client->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_clients');
    }

    public function update(User $user, Client $client): bool
    {
        return $user->hasPermissionTo('manage_clients')
            && $user->tenant_id === $client->tenant_id;
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->hasPermissionTo('manage_clients')
            && $user->tenant_id === $client->tenant_id;
    }

    public function restore(User $user, Client $client): bool
    {
        return $user->hasPermissionTo('manage_clients')
            && $user->tenant_id === $client->tenant_id;
    }

    public function invitePortalUser(User $user, Client $client): bool
    {
        return $user->hasPermissionTo('invite_client_portal')
            && $user->tenant_id === $client->tenant_id;
    }
}
