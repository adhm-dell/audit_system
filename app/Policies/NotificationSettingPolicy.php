<?php

namespace App\Policies;

use App\Models\NotificationSetting;
use App\Models\User;

class NotificationSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOwnerAdmin();
    }

    public function view(User $user, NotificationSetting $setting): bool
    {
        return $user->isOwnerAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isOwnerAdmin();
    }

    public function update(User $user, NotificationSetting $setting): bool
    {
        return $user->isOwnerAdmin();
    }

    public function delete(User $user, NotificationSetting $setting): bool
    {
        return $user->isOwnerAdmin();
    }
}
