<?php

namespace App\Policies;

use App\Models\OwnerWithdrawal;
use App\Models\User;

class OwnerWithdrawalPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, OwnerWithdrawal $withdrawal): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->canEdit();
    }

    public function update(User $user, OwnerWithdrawal $withdrawal): bool
    {
        return $user->canEdit();
    }

    public function delete(User $user, OwnerWithdrawal $withdrawal): bool
    {
        return $user->isOwnerAdmin();
    }
}
