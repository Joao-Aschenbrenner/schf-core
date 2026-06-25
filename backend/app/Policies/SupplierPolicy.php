<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Supplier;

class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_suppliers', 'web');
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $user->hasPermissionTo('manage_suppliers', 'web');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_suppliers', 'web');
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->hasPermissionTo('manage_suppliers', 'web');
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->hasRole('super_admin', 'web');
    }
}
