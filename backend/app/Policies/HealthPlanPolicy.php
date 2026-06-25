<?php

namespace App\Policies;

use App\Models\User;
use App\Models\HealthPlan;

class HealthPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_health_plans', 'web');
    }

    public function view(User $user, HealthPlan $healthPlan): bool
    {
        return $user->hasPermissionTo('manage_health_plans', 'web');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_health_plans', 'web');
    }

    public function update(User $user, HealthPlan $healthPlan): bool
    {
        return $user->hasPermissionTo('manage_health_plans', 'web');
    }

    public function delete(User $user, HealthPlan $healthPlan): bool
    {
        return $user->hasRole('super_admin', 'web');
    }
}
