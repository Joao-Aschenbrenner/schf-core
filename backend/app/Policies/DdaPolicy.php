<?php

namespace App\Policies;

use App\Models\Dda;
use App\Models\User;

class DdaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_dda', 'web');
    }

    public function view(User $user, Dda $dda): bool
    {
        return $user->hasPermissionTo('manage_dda', 'web');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_dda', 'web');
    }

    public function update(User $user, Dda $dda): bool
    {
        return $user->hasPermissionTo('manage_dda', 'web');
    }
}
