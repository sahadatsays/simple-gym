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
        return $user->can('rfid-cards.create');
    }

    public function delete(User $user, RfidCard $rfidCard): bool
    {
        return $user->can('rfid-cards.delete');
    }

    public function assign(User $user, RfidCard $rfidCard): bool
    {
        return $user->can('rfid-cards.assign');
    }

    public function issue(User $user): bool
    {
        return $user->can('rfid-cards.assign');
    }

    public function replace(User $user): bool
    {
        return $user->can('rfid-cards.replace');
    }

    public function disable(User $user, RfidCard $rfidCard): bool
    {
        return $user->can('rfid-cards.edit');
    }

    public function enable(User $user, RfidCard $rfidCard): bool
    {
        return $user->can('rfid-cards.edit');
    }

    public function returnCard(User $user, RfidCard $rfidCard): bool
    {
        return $user->can('rfid-cards.return');
    }

    public function markLost(User $user, RfidCard $rfidCard): bool
    {
        return $user->can('rfid-cards.edit');
    }
}
