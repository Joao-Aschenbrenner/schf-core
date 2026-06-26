<?php

namespace App\Events;

use App\Models\Organization;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrganizationActivated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Organization $organization;

    public bool $activated;

    public ?string $reason;

    public function __construct(Organization $organization, bool $activated, ?string $reason = null)
    {
        $this->organization = $organization;
        $this->activated = $activated;
        $this->reason = $reason;
    }
}
