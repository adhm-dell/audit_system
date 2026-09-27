<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOwnerAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isOwnerAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isOwnerAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isOwnerAdmin();
    }

    public function delete(User $user, User $model): bool
    {
        return false; // No delete — only activate/deactivate
    }
}
