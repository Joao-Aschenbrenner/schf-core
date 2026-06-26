<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserAssignedRole
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;

    public array $roles;

    public function __construct(User $user, array $roles)
    {
        $this->user = $user;
        $this->roles = $roles;
    }
}
