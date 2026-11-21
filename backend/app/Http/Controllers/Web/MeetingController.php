<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Meeting;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MeetingController extends Controller
{
    public function index(Request $request, Event $event)
    {
        Gate::authorize('viewWorkspace', $event);
        $search = trim($request->validate(['q' => 'nullable|string|max:120'])['q'] ?? '');
        $meetings = $event->meetings()->withCount('decisions')
            ->when($search !== '', fn ($query) => $query->where(fn ($match) => $match
                ->whereLike('title', "%{$search}%")->orWhereLike('notes', "%{$search}%")))
            ->orderByDesc('scheduled_at')->paginate(15)->withQueryString();
        return view('meetings.index', compact('event', 'meetings', 'search'));
    }

    public function create(Event $event)
    {
        Gate::authorize('manage', $event);
        return view('meetings.form', ['event' => $event, 'meeting' => new Meeting]);
    }

    public function show(Event $event, Meeting $meeting)
    {
        Gate::authorize('viewWorkspace', $event);
        $meeting->load(['decisions.tasks' => fn ($tasks) => $tasks
            ->where('event_id', $event->id)->select('id', 'decision_id', 'title')]);
        return view('meetings.show', compact('event', 'meeting'));
    }

    public function edit(Event $event, Meeting $meeting)
    {
        Gate::authorize('manage', $event);
        return view('meetings.form', compact('event', 'meeting'));
    }

    private function data(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'scheduled_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'notes' => ['nullable', 'string', 'max:15000'],
            'event_id' => ['prohibited'],
        ]);
        $data['scheduled_at'] = CarbonImmutable::parse($data['scheduled_at'], 'Asia/Makassar')->utc();
        return $data;
    }

    public function store(Request $request, Event $event)
    {
        Gate::authorize('manage', $event);
        $meeting = $event->meetings()->create($this->data($request));
        return redirect()->route('meetings.show', [$event, $meeting])->with('status', 'Rapat berhasil dibuat.');
    }

    public function update(Request $request, Event $event, Meeting $meeting)
    {
        Gate::authorize('manage', $event);
        $meeting->update($this->data($request));
        return redirect()->route('meetings.show', [$event, $meeting])->with('status', 'Rapat dan hasil rapat diperbarui.');
    }
}
