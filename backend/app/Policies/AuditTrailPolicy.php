<?php

namespace App\Policies;

use App\Models\User;
use App\Models\AuditTrail;

class AuditTrailPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_audit_trail', 'web');
    }

    public function view(User $user, AuditTrail $auditTrail): bool
    {
        return $user->hasPermissionTo('view_audit_trail', 'web');
    }
}
