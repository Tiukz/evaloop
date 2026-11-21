<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventSeriesRequest;
use App\Models\EventSeries;
use App\Models\Organization;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EventSeriesController extends Controller
{
    public function index(Organization $organization): View
    {
        Gate::authorize('manage', $organization);
        $series = $organization->eventSeries()->withCount('events')->orderBy('name')->paginate(12);

        return view('events.series.index', compact('organization', 'series'));
    }

    public function create(Organization $organization): View
    {
        Gate::authorize('manage', $organization);

        return view('events.series.form', compact('organization'));
    }

    public function show(Organization $organization, EventSeries $eventSeries): View
    {
        // Full history can include drafts: only the organization's active admin reads it.
        Gate::authorize('manage', $organization);
        $series = $eventSeries;
        $events = $series->events()->orderByDesc('starts_at')->paginate(12);

        return view('events.series.show', compact('organization', 'series', 'events'));
    }

    public function store(EventSeriesRequest $request, Organization $organization): \Illuminate\Http\RedirectResponse
    {
        $series = $organization->eventSeries()->create($request->safe()->only('name'));
        return redirect()->route('series.show', [$organization, $series])->with('status', 'Event Series berhasil dibuat.');
    }
}
