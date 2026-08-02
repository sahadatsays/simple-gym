<?php

namespace App\Policies;

use App\Models\GymSetting;
use App\Models\User;

class GymSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('settings.view');
    }

    public function view(User $user, GymSetting $gymSetting): bool
    {
        return $user->can('settings.view');
    }

    public function update(User $user, GymSetting $gymSetting): bool
    {
        return $user->can('settings.update');
    }
}
