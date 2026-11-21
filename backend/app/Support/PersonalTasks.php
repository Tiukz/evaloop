<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PersonalTasks
{
    public function query(User $user): HasMany
    {
        // Keep the existing assignment + workspace access boundary for every view/filter.
        return $user->tasks()->whereHas('event', fn ($query) => $query->where(function ($events) use ($user) {
            $events->whereHas('memberships', fn ($q) => $q->where('user_id', $user->id))
                ->orWhereHas('organization.memberships', fn ($q) => $q->active()->where('role', 'admin')->where('user_id', $user->id));
        }));
    }
}
