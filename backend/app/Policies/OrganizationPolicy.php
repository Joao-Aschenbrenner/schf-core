<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrganizationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('organizations.view');
    }

    public function view(User $user, Organization $organization): bool
    {
        return $user->hasPermission('organizations.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('organizations.create');
    }

    public function update(User $user, Organization $organization): bool
    {
        return $user->hasPermission('organizations.update');
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $user->hasPermission('organizations.delete');
    }

    public function deactivate(User $user, Organization $organization): bool
    {
        return $user->hasPermission('organizations.deactivate');
    }

    public function activate(User $user, Organization $organization): bool
    {
        return $user->hasPermission('organizations.activate');
    }

    public function setPrimary(User $user, Organization $organization): bool
    {
        return $user->hasPermission('organizations.set_primary');
    }
}