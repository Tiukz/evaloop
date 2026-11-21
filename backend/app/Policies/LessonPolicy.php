<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\Lesson;
use App\Models\User;

class LessonPolicy
{
    public function create(User $user, Event $event): bool
    {
        return $user->can('manage', $event);
    }

    public function update(User $user, Lesson $lesson): bool
    {
        return $lesson->approved_at === null && $user->can('manage', $lesson->event);
    }

    public function approve(User $user, Lesson $lesson): bool
    {
        return $user->isOrganizationAdmin($lesson->event->organization_id)
            && $lesson->event->event_series_id !== null
            && $lesson->event->eventSeries?->organization_id === $lesson->event->organization_id;
    }

    public function viewForEvent(User $user, Lesson $lesson, Event $event): bool
    {
        return $user->can('viewWorkspace', $event)
            && ($lesson->event_id === $event->id || $lesson->isInheritedBy($event));
    }

    public function actOn(User $user, Lesson $lesson, Event $event): bool
    {
        return $user->can('manage', $event) && $lesson->isInheritedBy($event);
    }
}
