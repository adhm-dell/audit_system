<?php

namespace App\Policies;

use App\Models\DailyEnvelope;
use App\Models\User;

class DailyEnvelopePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DailyEnvelope $envelope): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->canEdit();
    }

    public function update(User $user, DailyEnvelope $envelope): bool
    {
        return $user->canEdit();
    }

    public function delete(User $user, DailyEnvelope $envelope): bool
    {
        return $user->isOwnerAdmin();
    }
}
