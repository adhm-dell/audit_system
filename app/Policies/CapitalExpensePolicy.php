<?php

namespace App\Policies;

use App\Models\CapitalExpense;
use App\Models\User;

class CapitalExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CapitalExpense $expense): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->canEdit();
    }

    public function update(User $user, CapitalExpense $expense): bool
    {
        return $user->canEdit();
    }

    public function delete(User $user, CapitalExpense $expense): bool
    {
        return $user->isOwnerAdmin();
    }
}
