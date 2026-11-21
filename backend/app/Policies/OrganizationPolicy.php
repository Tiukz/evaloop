<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function view(User $user, Organization $organization): bool
    {
        return $organization->memberships()->active()->where('user_id', $user->id)->exists();
    }

    public function administer(User $user, Organization $organization): bool
    {
        // Basic institutional administration does not grant workspace access.
        return $user->isUniversityAdmin($organization->university_id);
    }

    public function manage(User $user, Organization $organization): bool
    {
        return $user->isOrganizationAdmin($organization->id);
    }
}
