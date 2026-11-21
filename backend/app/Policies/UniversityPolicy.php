<?php

namespace App\Policies;

use App\Models\University;
use App\Models\User;

class UniversityPolicy
{
    public function manage(User $user, University $university): bool
    {
        return $user->isUniversityAdmin($university->id);
    }
}
