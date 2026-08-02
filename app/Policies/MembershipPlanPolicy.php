<?php

namespace App\Policies;

use App\Models\MembershipPlan;
use App\Models\User;

class MembershipPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('membership-plans.view');
    }

    public function view(User $user, MembershipPlan $membershipPlan): bool
    {
        return $user->can('membership-plans.view');
    }

    public function create(User $user): bool
    {
        return $user->can('membership-plans.create');
    }

    public function update(User $user, MembershipPlan $membershipPlan): bool
    {
        return $user->can('membership-plans.edit');
    }

    public function delete(User $user, MembershipPlan $membershipPlan): bool
    {
        return $user->can('membership-plans.delete');
    }
}
