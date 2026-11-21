<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\Organization;
use App\Models\User;

class EventPolicy
{
    public function create(User $user, Organization $organization): bool
    {
        return $user->isOrganizationAdmin($organization->id);
    }

    public function publish(User $user, Event $event): bool
    {
        return $user->isOrganizationAdmin($event->organization_id);
    }

    public function cancel(User $user, Event $event): bool
    {
        return $user->isOrganizationAdmin($event->organization_id);
    }

    public function complete(User $user, Event $event): bool
    {
        return $user->isOrganizationAdmin($event->organization_id);
    }

    public function assignLeader(User $user, Event $event): bool
    {
        return $user->isOrganizationAdmin($event->organization_id);
    }

    public function viewWorkspace(User $user, Event $event): bool
    {
        return $user->isOrganizationAdmin($event->organization_id)
            || $event->memberships()->where('user_id', $user->id)->exists();
    }

    public function manage(User $user, Event $event): bool
    {
        return $user->isOrganizationAdmin($event->organization_id)
            || $event->memberships()->where('user_id', $user->id)->where('role', 'coordinator')->exists();
    }
}
