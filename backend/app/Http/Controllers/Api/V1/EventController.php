<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RadarFilterRequest;
use App\Http\Resources\Api\V1\PublicEventResource;
use App\Models\Event;
use App\Models\Faculty;
use App\Models\Organization;
use App\Models\StudyProgram;
use App\Support\EventRadar;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventController extends Controller
{
    public function index(RadarFilterRequest $request, EventRadar $radar): AnonymousResourceCollection
    {
        $filters = array_filter($request->validated(), fn ($value) => $value !== null && $value !== '');
        $now = now()->toImmutable();
        $query = $radar->query($filters, $now);
        $featured = (clone $query)->where('status', 'published')
            ->where('starts_at', '>', $now)->where('published_at', '<=', $now)
            ->whereColumn('ends_at', '>', 'starts_at')->orderBy('starts_at')->orderBy('id')->first();

        return PublicEventResource::collection($radar->paginate($query, $now, $filters))->additional([
            'radar' => [
                'reference_time' => $now->toISOString(),
                'featured' => $featured ? (new PublicEventResource($featured))->resolve($request) : null,
                'faculties' => Faculty::orderBy('name')->get(['id', 'name']),
                'study_programs' => StudyProgram::orderBy('name')->get(['id', 'name', 'faculty_id']),
                'organizations' => Organization::whereHas('events', fn ($events) => $events->published())
                    ->orderBy('name')->get(['id', 'name']),
            ],
        ]);
    }

    public function show(int $event): PublicEventResource
    {
        return new PublicEventResource(Event::published()->with(['organization.faculty', 'organization.studyProgram'])->findOrFail($event));
    }
}
