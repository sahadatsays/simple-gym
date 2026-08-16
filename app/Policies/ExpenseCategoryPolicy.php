<?php

namespace App\Policies;

use App\Models\ExpenseCategory;
use App\Models\User;

class ExpenseCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('expense-categories.view');
    }

    public function view(User $user, ExpenseCategory $expenseCategory): bool
    {
        return $user->can('expense-categories.view');
    }

    public function create(User $user): bool
    {
        return $user->can('expense-categories.create');
    }

    public function update(User $user, ExpenseCategory $expenseCategory): bool
    {
        return $user->can('expense-categories.edit');
    }

    public function delete(User $user, ExpenseCategory $expenseCategory): bool
    {
        return $user->can('expense-categories.delete');
    }
}
