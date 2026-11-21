<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Meeting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $universities = $user->universityMemberships()->active()->where('role', 'admin')->with('university')->get()->pluck('university');
        $adminOrganizations = $user->organizationMemberships()->active()->where('role', 'admin')->with('organization')->get()->pluck('organization');
        $eventMembershipRoles = $user->eventMemberships()->pluck('role', 'event_id');
        $eventRoles = $eventMembershipRoles->values();
        // Read-only dashboard projection; identical membership scope to Event Saya/Task Tracking.
        $visibleEvent = fn (Builder $query) => $query->where(fn (Builder $scope) => $scope
            ->whereHas('memberships', fn (Builder $members) => $members->where('user_id', $user->id))
            ->orWhereHas('organization.memberships', fn (Builder $members) => $members->active()->where('user_id', $user->id)->where('role', 'admin')));
        $activeEvents = Event::where($visibleEvent)->whereIn('status', ['draft', 'published'])->where('ends_at', '>', now())
            ->with('organization')->withCount(['tasks', 'tasks as done_tasks_count' => fn (Builder $tasks) => $tasks->where('status', 'done')])
            ->orderBy('starts_at')->limit(4)->get();
        $myTasks = $user->tasks()->whereHas('event', $visibleEvent);
        $taskCounts = (clone $myTasks)->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');
        $priorityTasks = (clone $myTasks)->where('status', '!=', 'done')->with('event')
            ->orderByRaw('due_at IS NULL')->orderBy('due_at')->orderBy('id')->limit(5)->get();
        $upcomingMeetings = Meeting::whereHas('event', fn (Builder $events) => $visibleEvent($events)->whereIn('status', ['draft', 'published']))
            ->where('scheduled_at', '>=', now())->with('event')->orderBy('scheduled_at')->limit(3)->get();
        return view('dashboard.index', compact('universities', 'adminOrganizations', 'eventRoles', 'activeEvents', 'taskCounts', 'priorityTasks', 'upcomingMeetings', 'eventMembershipRoles'));
    }

    public function organizations(Request $request): View
    {
        $activeMembership = fn ($members) => $members->active()->where('user_id', $request->user()->id);
        $organizations = Organization::withWhereHas('memberships', $activeMembership)
            ->with(['university', 'faculty', 'studyProgram'])
            ->orderBy('name')->paginate(12);

        $universities = $request->user()->universityMemberships()->active()->where('role', 'admin')
            ->with('university')->get()->pluck('university');

        return view('dashboard.organizations', compact('organizations', 'universities'));
    }

    public function events(Request $request): View
    {
        $search = trim($request->validate(['q' => 'nullable|string|max:120'])['q'] ?? '');
        $userId = $request->user()->id;
        $visibleEvents = Event::where(fn (Builder $query) => $query
            ->whereHas('memberships', fn (Builder $members) => $members->where('user_id', $userId))
            ->orWhereHas('organization.memberships', fn (Builder $members) => $members
                ->active()->where('user_id', $userId)->where('role', 'admin')))
            ->with('organization');
        $featuredEvent = (clone $visibleEvents)->whereIn('status', ['draft', 'published'])
            ->where('ends_at', '>', now())->orderBy('starts_at')->orderBy('id')->first()
            ?? (clone $visibleEvents)->orderByDesc('starts_at')->orderByDesc('id')->first();
        $events = $visibleEvents->when($search !== '', fn (Builder $query) =>
            $query->where(fn (Builder $match) => $match->whereLike('title', "%{$search}%")
                ->orWhereHas('organization', fn (Builder $organization) => $organization->whereLike('name', "%{$search}%"))))
            ->orderBy('starts_at')->orderBy('id')->paginate(12)->withQueryString();

        $isInstitutionAdmin = $request->user()->is_platform_admin
            || $request->user()->universityMemberships()->active()->where('role', 'admin')->exists();
        return view('dashboard.events', compact('events', 'featuredEvent', 'isInstitutionAdmin', 'search'));
    }
}
