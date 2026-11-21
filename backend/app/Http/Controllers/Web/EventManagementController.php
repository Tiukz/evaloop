<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use App\Http\Requests\EventStatusRequest;
use App\Models\Event;
use App\Models\Organization;
use App\Models\StudyProgram;
use App\Support\WorkspaceImage;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;

class EventManagementController extends Controller
{
    public function create(Organization $organization): View
    {
        Gate::authorize('create', [Event::class, $organization]);

        return $this->form($organization, new Event);
    }

    public function edit(Event $event): View
    {
        Gate::authorize('manage', $event);
        $event->load('targets');

        return $this->form($event->organization, $event);
    }

    private function form(Organization $organization, Event $event): View
    {
        $series = $organization->eventSeries()->orderBy('name')->get();
        $programs = StudyProgram::whereHas('faculty', fn ($q) => $q->where('university_id', $organization->university_id))->orderBy('name')->get();

        return view('events.form', compact('organization', 'event', 'series', 'programs'));
    }

    private function eventData(EventRequest $request): array
    {
        $data = $request->safe()->only(['title', 'description', 'venue', 'event_series_id']);
        foreach (['starts_at', 'ends_at'] as $key) {
            $data[$key] = CarbonImmutable::parse($request->validated($key), 'Asia/Makassar')->utc();
        }
        return $data;
    }

    public function store(EventRequest $request, WorkspaceImage $images, Organization $organization): RedirectResponse
    {
        $event = $images->save($request->validated('poster'), 'event-posters', 'poster', function (?string $path) use ($request, $organization) {
            $organization->lockForUpdate()->findOrFail($organization->id);
            Gate::authorize('create', [Event::class, $organization]);
            $event = $organization->events()->create($this->eventData($request) + ['status' => 'draft']);
            if ($path !== null) {
                $event->forceFill(['poster_path' => $path])->save();
            }
            $event->targets()->sync($request->validated('target_program_ids', []));

            return [$event, null];
        });
        return redirect()->route('workspace.overview', $event)->with('status', 'Draft event berhasil dibuat.');
    }

    public function update(EventRequest $request, WorkspaceImage $images, Event $event): RedirectResponse
    {
        $images->save($request->validated('poster'), 'event-posters', 'poster', function (?string $path) use ($request, $event) {
            $event = Event::lockForUpdate()->findOrFail($event->id);
            Gate::authorize('manage', $event);
            $data = $this->eventData($request);
            if (array_key_exists('event_series_id', $data) && (string) $data['event_series_id'] !== (string) $event->event_series_id
                && ($event->lessons()->whereNotNull('approved_at')->exists() || $event->tasks()->whereNotNull('source_lesson_id')->exists())) {
                throw ValidationException::withMessages(['event_series_id' => 'Seri tidak dapat diubah setelah evaluasi disetujui atau tindak lanjut legacy ditautkan.']);
            }
            // Preserve the chronology of existing cross-edition source links when rescheduling.
            if ($event->tasks()->whereHas('sourceLesson.event', fn ($source) => $source->where('id', '!=', $event->id)
                ->where(fn ($dates) => $dates->where('ends_at', '>', $data['starts_at'])->orWhere('starts_at', '>=', $data['starts_at'])))->exists()
                || $event->lessons()->whereHas('inspiredTasks.event', fn ($target) => $target->where('id', '!=', $event->id)
                    ->where(fn ($dates) => $dates->where('starts_at', '<', $data['ends_at'])->orWhere('starts_at', '<=', $data['starts_at'])))->exists()) {
                throw ValidationException::withMessages(['starts_at' => 'Jadwal ini melanggar urutan edisi pada tindak lanjut legacy yang sudah ditautkan.']);
            }
            $previous = $event->poster_path;
            $event->fill($data);
            if ($path !== null) {
                $event->forceFill(['poster_path' => $path]);
            }
            $event->save();
            $event->targets()->sync($request->validated('target_program_ids', []));

            return [$event, $previous];
        });
        return redirect()->route('workspace.overview', $event)->with('status', 'Event berhasil diperbarui.');
    }

    public function publication(Event $event): View
    {
        Gate::authorize('publish', $event);

        return view('events.publish', compact('event'));
    }

    public function publish(EventStatusRequest $request, Event $event): RedirectResponse
    {
        DB::transaction(function () use ($event) {
            $event = Event::lockForUpdate()->findOrFail($event->id);
            Gate::authorize('publish', $event);
            if ($event->status !== 'draft' || ! $event->starts_at || ! $event->ends_at || $event->starts_at->gte($event->ends_at)) {
                throw ValidationException::withMessages(['status' => 'Hanya draft dengan jadwal valid yang dapat dipublikasikan.']);
            }
            $event->update(['status' => 'published', 'published_at' => now()]);
        });
        return redirect()->route('workspace.overview', $event)->with('status', 'Event berhasil dipublikasikan.');
    }

    public function completion(Event $event): View
    {
        Gate::authorize('complete', $event);

        return view('events.complete', compact('event'));
    }

    public function complete(EventStatusRequest $request, Event $event): RedirectResponse
    {
        DB::transaction(function () use ($event) {
            $event = Event::lockForUpdate()->findOrFail($event->id);
            Gate::authorize('complete', $event);
            if (! $event->canBeCompleted()) {
                throw ValidationException::withMessages(['status' => 'Hanya event yang sudah dipublikasikan yang dapat ditandai selesai.']);
            }
            $event->update(['status' => 'completed']);
        });

        return redirect()->route('workspace.overview', $event)->with('status', 'Event ditandai selesai.');
    }

    public function cancellation(Event $event): View
    {
        Gate::authorize('cancel', $event);

        return view('events.cancel', compact('event'));
    }

    public function cancel(EventStatusRequest $request, Event $event): RedirectResponse
    {
        DB::transaction(function () use ($event) {
            $event = Event::lockForUpdate()->findOrFail($event->id);
            Gate::authorize('cancel', $event);
            if (! in_array($event->status, ['draft', 'published'], true)) {
                throw ValidationException::withMessages(['status' => 'Hanya draft atau event published yang dapat dibatalkan.']);
            }
            $event->update(['status' => 'cancelled']);
        });
        return redirect()->route('workspace.overview', $event)->with('status', 'Event dibatalkan; arsip tetap tersimpan.');
    }
}
