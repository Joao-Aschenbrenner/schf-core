<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->canAccessAdmin();
    }

    public function view(User $user, User $model): bool
    {
        if ($user->is_master) return true;
        return $user->hasPermissionTo('manage_users', 'sanctum');
    }

    public function create(User $user): bool
    {
        if ($user->is_master) return true;
        return $user->hasPermissionTo('manage_users', 'sanctum');
    }

    public function update(User $user, User $model): bool
    {
        if ($user->is_master) return true;
        if ($model->is_master && !$user->is_master) return false;
        return $user->hasPermissionTo('manage_users', 'sanctum');
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->is_master) return true;
        if ($model->is_master) return false;
        if ($model->id === $user->id) return false;
        return $user->hasPermissionTo('manage_users', 'sanctum');
    }

    public function restore(User $user, User $model): bool
    {
        return $user->is_master || $user->hasPermissionTo('manage_users', 'sanctum');
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->is_master && $model->id !== $user->id;
    }

    public function toggleMaster(User $user, User $model): bool
    {
        return $user->is_master && $model->id !== $user->id;
    }

    public function resetPassword(User $user, User $model): bool
    {
        if ($user->is_master) return true;
        return $user->hasPermissionTo('manage_users', 'sanctum');
    }

    public function manageRoles(User $user): bool
    {
        return $user->is_master;
    }
}