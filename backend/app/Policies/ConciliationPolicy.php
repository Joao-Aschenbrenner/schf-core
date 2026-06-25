<?php

namespace App\Policies;

use App\Models\BankStatement;
use App\Models\BankStatementItem;
use App\Models\User;

class ConciliationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_conciliation', 'web');
    }

    public function view(User $user, BankStatement $statement): bool
    {
        return $user->hasPermissionTo('manage_conciliation', 'web');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_conciliation', 'web');
    }

    public function update(User $user, BankStatementItem $item): bool
    {
        return $user->hasPermissionTo('manage_conciliation', 'web');
    }
}
