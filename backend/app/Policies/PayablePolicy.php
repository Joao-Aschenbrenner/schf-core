<?php

namespace App\Policies;

use App\Models\Payable;
use App\Models\User;

class PayablePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_payables', 'web');
    }

    public function view(User $user, Payable $payable): bool
    {
        return $user->hasPermissionTo('manage_payables', 'web');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_payables', 'web');
    }

    public function update(User $user, Payable $payable): bool
    {
        return $user->hasPermissionTo('manage_payables', 'web');
    }

    public function delete(User $user, Payable $payable): bool
    {
        return $user->hasPermissionTo('manage_payables', 'web');
    }

    public function approve(User $user, Payable $payable): bool
    {
        return $user->hasPermissionTo('manage_payables', 'web');
    }

    public function pay(User $user, Payable $payable): bool
    {
        return $user->hasPermissionTo('manage_payables', 'web');
    }
}
