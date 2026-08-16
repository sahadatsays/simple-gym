<?php

namespace App\Policies;

use App\Models\Investment;
use App\Models\User;

class InvestmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('investments.view');
    }

    public function view(User $user, Investment $investment): bool
    {
        return $user->can('investments.view');
    }

    public function create(User $user): bool
    {
        return $user->can('investments.create');
    }

    public function update(User $user, Investment $investment): bool
    {
        return $user->can('investments.edit');
    }

    public function delete(User $user, Investment $investment): bool
    {
        return $user->can('investments.delete');
    }
}
