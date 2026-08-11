<?php

namespace App\Policies;

use App\Models\RfidCard;
use App\Models\User;

class RfidCardPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('rfid-cards.view');
    }

    public function view(User $user, RfidCard $rfidCard): bool
    {
        return $user->can('rfid-cards.view');
    }

    public function create(User $user): bool
    {
        return $user->can('rfid-cards.manage');
    }

    public function assign(User $user, RfidCard $rfidCard): bool
    {
        return $user->can('rfid-cards.manage');
    }

    public function replace(User $user): bool
    {
        return $user->can('rfid-cards.manage');
    }

    public function disable(User $user, RfidCard $rfidCard): bool
    {
        return $user->can('rfid-cards.manage');
    }

    public function enable(User $user, RfidCard $rfidCard): bool
    {
        return $user->can('rfid-cards.manage');
    }
}
