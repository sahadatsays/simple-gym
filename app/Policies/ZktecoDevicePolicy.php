<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ZktecoDevice;

class ZktecoDevicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('zkteco-devices.view');
    }

    public function view(User $user, ZktecoDevice $device): bool
    {
        return $user->can('zkteco-devices.view');
    }

    public function manage(User $user, ZktecoDevice $device): bool
    {
        return $user->can('zkteco-devices.manage');
    }
}
