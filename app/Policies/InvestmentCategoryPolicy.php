<?php

namespace App\Policies;

use App\Models\InvestmentCategory;
use App\Models\User;

class InvestmentCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('investments.view');
    }

    public function view(User $user, InvestmentCategory $investmentCategory): bool
    {
        return $user->can('investments.view');
    }

    public function create(User $user): bool
    {
        return $user->can('investments.create');
    }

    public function update(User $user, InvestmentCategory $investmentCategory): bool
    {
        return $user->can('investments.edit');
    }

    public function delete(User $user, InvestmentCategory $investmentCategory): bool
    {
        return $user->can('investments.delete');
    }
}
