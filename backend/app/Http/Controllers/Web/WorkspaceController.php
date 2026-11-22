<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Lesson;
use App\Support\ScheduleConflicts;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    public function show(Event $event, ScheduleConflicts $conflicts, string $section = 'overview'): View
    {
        Gate::authorize('viewWorkspace', $event);

        $event->load(['organization.faculty', 'organization.studyProgram', 'eventSeries', 'targets']);
        $archived = in_array($event->status, ['completed', 'cancelled'], true);
        $canManage = Gate::allows('manage', $event);
        $taskCounts = $event->tasks()->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');
        $overviewTasks = $event->tasks()->with('assignee')
            ->when(! $canManage && ! $archived, fn ($query) => $query->where('assignee_user_id', auth()->id()))
            ->when(! $archived, fn ($query) => $query->where('status', '!=', 'done'))
            ->when($archived, fn ($query) => $query->orderByRaw("CASE WHEN status = 'done' THEN 0 ELSE 1 END"))
            ->orderByRaw('due_at IS NULL')->orderBy('due_at')->orderBy('id')->limit(3)->get();
        $upcomingMeetings = $archived ? collect() : $event->meetings()->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at')->limit(3)->get();
        $recentMeeting = $archived ? $event->meetings()->where('scheduled_at', '<=', now())
            ->orderByDesc('scheduled_at')->orderByDesc('id')->first() : null;
        $meetingDecision = $recentMeeting?->decisions()->latest('id')->first();
        $approvedLessons = $event->status === 'completed' ? $event->lessons()->where('visibility', 'series')
            ->whereNotNull('approved_at')->where('approved_at', '<=', now())->orderByDesc('id')->limit(2)->get() : collect();
        $coordinator = $event->memberships()->where('role', 'coordinator')->with('user')->first();
        $followUps = fn ($query) => $query->where('event_id', $event->id);
        $inheritedLessons = Lesson::inheritedBy($event)
            ->with(['event', 'inspiredTasks' => $followUps])
            ->withExists(['inspiredTasks as has_follow_up' => $followUps])
            ->when($canManage, fn ($query) => $query->orderBy('has_follow_up'))
            ->orderByDesc('id')->limit($canManage ? 2 : 1)->get();
        $collisionPreview = $canManage && ! $archived ? $conflicts->inspect($event, page: 1) : null;
        // A series is not permission to inspect other editions' private workspaces.
        $previousEdition = $nextEdition = null;
        if ($event->event_series_id && $event->eventSeries?->organization_id === $event->organization_id) {
            $user = auth()->user();
            $editions = Event::where('event_series_id', $event->event_series_id)->where('organization_id', $event->organization_id)
                ->where('id', '!=', $event->id)->where(fn ($scope) => $scope
                    ->whereHas('memberships', fn ($members) => $members->where('user_id', $user->id))
                    ->orWhereHas('organization.memberships', fn ($members) => $members->active()->where('role', 'admin')->where('user_id', $user->id)));
            $previousEdition = (clone $editions)->where('starts_at', '<', $event->starts_at)->orderByDesc('starts_at')->orderByDesc('id')->first();
            $nextEdition = (clone $editions)->where('starts_at', '>', $event->starts_at)->orderBy('starts_at')->orderBy('id')->first();
        }
        return view('events.workspace', compact('event', 'section', 'archived', 'taskCounts', 'overviewTasks', 'upcomingMeetings',
            'recentMeeting', 'meetingDecision', 'approvedLessons', 'coordinator', 'canManage', 'inheritedLessons', 'collisionPreview',
            'previousEdition', 'nextEdition'));
    }

    public function conflicts(Event $event, ScheduleConflicts $conflicts): View
    {
        Gate::authorize('manage', $event);
        return view('events.conflicts', ['event' => $event] + $conflicts->inspect($event));
    }
}
