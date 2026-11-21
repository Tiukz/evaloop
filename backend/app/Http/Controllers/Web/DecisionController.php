<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Meeting;
use App\Models\Decision;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DecisionController extends Controller
{
    public function edit(Event $event, Meeting $meeting, Decision $decision)
    {
        Gate::authorize('manage', $event);
        return view('meetings.decision', compact('event', 'meeting', 'decision'));
    }

    private function data(Request $request): array
    {
        return $request->validate(['summary' => ['required', 'string', 'max:15000'],
            'rationale' => ['nullable', 'string', 'max:15000'], 'meeting_id' => ['prohibited'], 'event_id' => ['prohibited']]);
    }

    public function store(Request $request, Event $event, Meeting $meeting)
    {
        Gate::authorize('manage', $event);
        $meeting->decisions()->create($this->data($request));
        return redirect()->route('meetings.show', [$event, $meeting])->with('status', 'Keputusan berhasil dicatat.');
    }

    public function update(Request $request, Event $event, Meeting $meeting, Decision $decision)
    {
        Gate::authorize('manage', $event);
        $decision->update($this->data($request));
        return redirect()->route('meetings.show', [$event, $meeting])->with('status', 'Keputusan diperbarui.');
    }
}
