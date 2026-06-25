<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ExpenseCategory;

class ExpenseCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_expense_categories', 'web');
    }

    public function view(User $user, ExpenseCategory $category): bool
    {
        return $user->hasPermissionTo('manage_expense_categories', 'web');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_expense_categories', 'web');
    }

    public function update(User $user, ExpenseCategory $category): bool
    {
        return $user->hasPermissionTo('manage_expense_categories', 'web');
    }

    public function delete(User $user, ExpenseCategory $category): bool
    {
        return $user->hasRole('super_admin', 'web');
    }
}
