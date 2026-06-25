<?php

namespace App\Policies;

use App\Models\User;
use App\Models\BankAccount;

class BankAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_bank_accounts', 'web');
    }

    public function view(User $user, BankAccount $bankAccount): bool
    {
        return $user->hasPermissionTo('manage_bank_accounts', 'web');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_bank_accounts', 'web');
    }

    public function update(User $user, BankAccount $bankAccount): bool
    {
        return $user->hasPermissionTo('manage_bank_accounts', 'web');
    }

    public function delete(User $user, BankAccount $bankAccount): bool
    {
        return $user->hasRole('super_admin', 'web');
    }
}
