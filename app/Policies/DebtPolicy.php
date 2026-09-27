<?php

namespace App\Policies;

use App\Models\Debt;
use App\Models\User;

class DebtPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Debt $debt): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->canEdit();
    }

    public function update(User $user, Debt $debt): bool
    {
        return $user->isOwnerAdmin();
    }

    public function delete(User $user, Debt $debt): bool
    {
        return $user->isOwnerAdmin();
    }
}
