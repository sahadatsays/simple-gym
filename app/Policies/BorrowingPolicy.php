<?php

namespace App\Policies;

use App\Models\Borrowing;
use App\Models\User;

class BorrowingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('borrowings.view');
    }

    public function view(User $user, Borrowing $borrowing): bool
    {
        return $user->can('borrowings.view');
    }

    public function create(User $user): bool
    {
        return $user->can('borrowings.create');
    }

    public function update(User $user, Borrowing $borrowing): bool
    {
        return $user->can('borrowings.edit');
    }

    public function delete(User $user, Borrowing $borrowing): bool
    {
        return $user->can('borrowings.delete');
    }
}
