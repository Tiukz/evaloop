<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\RadarFilterRequest;
use App\Models\Event;
use App\Models\Faculty;
use App\Models\Organization;
use App\Models\StudyProgram;
use App\Support\EventRadar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(RadarFilterRequest $request, EventRadar $radar): View
    {
        $filters = array_filter($request->validated(), fn ($value) => $value !== null && $value !== '');
        $radarNow = now()->toImmutable();
        $period = $filters['period'] ?? 'all';
        $query = $radar->query($filters, $radarNow);
        // Editorial projection uses the same public/search/period filters, not private workspace data.
        $featured = (clone $query)->where('status', 'published')
            ->where('starts_at', '>', $radarNow)->where('published_at', '<=', $radarNow)
            ->whereColumn('ends_at', '>', 'starts_at')->orderBy('starts_at')->orderBy('id')->first();
        $events = $radar->paginate($query, $radarNow, $filters);
        $faculties = Faculty::orderBy('name')->get()->keyBy('id');
        $programs = StudyProgram::orderBy('name')->get();
        $organizations = Organization::whereHas('events', fn (Builder $events) => $events->published())->orderBy('name')->get();

        return view('events.index', compact('events', 'filters', 'faculties', 'programs', 'organizations', 'period', 'radarNow', 'featured'));
    }

    public function show(int $id): View
    {
        $event = Event::published()->with(['organization.faculty', 'organization.studyProgram'])->findOrFail($id);

        return view('events.show', compact('event'));
    }
}
