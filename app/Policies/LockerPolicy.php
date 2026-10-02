<?php

namespace App\Policies;

use App\Models\Locker;
use App\Models\User;

class LockerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('lockers.view');
    }

    public function view(User $user, Locker $locker): bool
    {
        return $user->can('lockers.view');
    }

    public function create(User $user): bool
    {
        return $user->can('lockers.create');
    }

    public function update(User $user, Locker $locker): bool
    {
        return $user->can('lockers.edit');
    }

    public function delete(User $user, Locker $locker): bool
    {
        return $user->can('lockers.delete');
    }
}
