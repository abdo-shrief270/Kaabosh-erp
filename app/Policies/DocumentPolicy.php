<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Document\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_documents');
    }

    public function view(User $user, Document $document): bool
    {
        return $user->hasPermissionTo('manage_documents')
            && $user->tenant_id === $document->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_documents');
    }

    public function update(User $user, Document $document): bool
    {
        return $user->hasPermissionTo('manage_documents')
            && $user->tenant_id === $document->tenant_id;
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->hasPermissionTo('manage_documents')
            && $user->tenant_id === $document->tenant_id;
    }
}
