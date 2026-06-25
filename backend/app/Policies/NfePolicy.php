<?php

namespace App\Policies;

use App\Models\Nfe;
use App\Models\User;

class NfePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_nfe', 'web');
    }

    public function view(User $user, Nfe $nfe): bool
    {
        return $user->hasPermissionTo('manage_nfe', 'web');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_nfe', 'web');
    }

    public function update(User $user, Nfe $nfe): bool
    {
        return $user->hasPermissionTo('manage_nfe', 'web');
    }

    public function delete(User $user, Nfe $nfe): bool
    {
        return $user->hasPermissionTo('manage_nfe', 'web');
    }
}
