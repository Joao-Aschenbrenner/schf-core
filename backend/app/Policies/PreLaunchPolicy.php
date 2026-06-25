<?php

namespace App\Policies;

use App\Models\PreLaunch;
use App\Models\User;

class PreLaunchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_pre_launches', 'web');
    }

    public function view(User $user, PreLaunch $preLaunch): bool
    {
        return $user->hasPermissionTo('manage_pre_launches', 'web');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_pre_launches', 'web');
    }

    public function update(User $user, PreLaunch $preLaunch): bool
    {
        return $user->hasPermissionTo('manage_pre_launches', 'web');
    }
}
