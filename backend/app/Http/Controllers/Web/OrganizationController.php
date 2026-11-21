<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function show(Organization $organization, string $section = 'overview'): View
    {
        Gate::authorize($section === 'overview' ? 'view' : 'manage', $organization);
        $organization->load(['university', 'faculty', 'studyProgram']);
        if ($section === 'overview') {
            $user = auth()->user();
            $canManage = $user->can('manage', $organization);
            $membership = $organization->memberships()->active()->where('user_id', $user->id)->first();
            $hubEvents = $organization->events()
                ->when(! $canManage, fn (Builder $query) => $query->where(fn (Builder $visible) => $visible
                    ->whereHas('memberships', fn (Builder $members) => $members->where('user_id', $user->id))
                    ->orWhere(fn (Builder $public) => $public->published())))
                ->with(['memberships' => fn ($query) => $query->where('user_id', $user->id)])
                ->orderByRaw("CASE WHEN status IN ('draft', 'published') AND ends_at > ? THEN 0 ELSE 1 END", [now()])
                ->orderByRaw("CASE WHEN status = 'draft' THEN 0 WHEN status = 'published' THEN 1 ELSE 2 END")
                ->orderBy('starts_at')->orderBy('id')->limit(5)->get();
            $editionScope = fn ($query) => $query->where('organization_id', $organization->id)
                ->when(! $canManage, fn (Builder $editions) => $editions->whereHas('memberships', fn (Builder $members) => $members->where('user_id', $user->id)));
            $hubSeries = $organization->eventSeries()
                ->when(! $canManage, fn (Builder $query) => $query->whereHas('events', $editionScope))
                ->withCount(['events' => $editionScope])
                ->with(['events' => fn ($query) => $editionScope($query)->orderByDesc('starts_at')->orderByDesc('id')->limit(2)])
                ->orderByDesc('id')->limit(3)->get();
            $memberCounts = $canManage ? $organization->memberships()->active()
                ->selectRaw('role, COUNT(*) AS total')->groupBy('role')->pluck('total', 'role') : null;
            $membershipTotal = $canManage ? $organization->memberships()->count() : null;

            return view('organizations.hub', compact('organization', 'canManage', 'membership', 'hubEvents', 'hubSeries', 'memberCounts', 'membershipTotal'));
        }
        $events = $section === 'events' ? $organization->events()->orderByDesc('starts_at')->paginate(12) : null;

        return view('organizations.show', compact('organization', 'section', 'events'));
    }
}
