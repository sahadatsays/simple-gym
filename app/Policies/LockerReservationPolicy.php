<?php

namespace App\Policies;

use App\Models\LockerReservation;
use App\Models\User;

class LockerReservationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('lockers.view');
    }

    public function view(User $user, LockerReservation $lockerReservation): bool
    {
        return $user->can('lockers.view');
    }

    public function create(User $user): bool
    {
        return $user->can('lockers.reserve');
    }

    public function renew(User $user, LockerReservation $lockerReservation): bool
    {
        return $user->can('lockers.renew');
    }

    public function cancel(User $user, LockerReservation $lockerReservation): bool
    {
        return $user->can('lockers.cancel');
    }
}
