<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PersonalTaskResource;
use App\Models\Decision;
use App\Models\Event;
use App\Models\Meeting;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class MeetingController extends Controller
{
    public function index(Request $request, Event $event): JsonResponse
    {
        Gate::authorize('viewWorkspace', $event);
        $filters = $request->validate(['q' => 'nullable|string|max:120', 'page' => 'nullable|integer|min:1']);
        $search = trim($filters['q'] ?? '');
        $meetings = $event->meetings()->withCount('decisions')
            ->when($search !== '', fn ($query) => $query->where(fn ($match) => $match
                ->whereLike('title', "%{$search}%")->orWhereLike('notes', "%{$search}%")))
            ->orderByDesc('scheduled_at')->orderByDesc('id')->paginate(15);

        return $this->respond([
            'event' => $this->eventData($event),
            'reference_time' => now()->toISOString(),
            'data' => $meetings->getCollection()->map(fn (Meeting $meeting) => $this->meetingData($meeting) + [
                'decisions_count' => (int) $meeting->decisions_count,
                'notes_excerpt' => Str::limit(Str::squish($meeting->notes ?? ''), 240),
            ]),
            'meta' => ['total' => $meetings->total(), 'current_page' => $meetings->currentPage(), 'last_page' => $meetings->lastPage()],
        ]);
    }

    public function show(Event $event, Meeting $meeting): JsonResponse
    {
        Gate::authorize('viewWorkspace', $event);
        $meeting->load(['decisions.tasks' => fn ($tasks) => $tasks
            ->where('event_id', $event->id)->select('id', 'decision_id', 'title', 'status')->orderBy('id')]);

        return $this->respond(['data' => $this->meetingData($meeting) + [
            'event' => $this->eventData($event),
            'notes' => $meeting->notes,
            'decisions' => $meeting->decisions->map(fn (Decision $decision) => [
                'id' => $decision->id,
                'summary' => $decision->summary,
                'rationale' => $decision->rationale,
                'tasks' => $decision->tasks->map(fn (Task $task) => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'status' => $task->status,
                ]),
            ]),
        ]]);
    }

    public function task(Request $request, Event $event, Meeting $meeting, int $task): JsonResponse
    {
        Gate::authorize('viewWorkspace', $event);
        $task = $event->tasks()->whereHas('decision', fn ($decision) => $decision->where('meeting_id', $meeting->id))
            ->with(['assignee:id,name', 'sourceLesson.event', 'event.eventSeries', 'decision.meeting'])->findOrFail($task);

        return $this->respond(['data' => [
            'can_update_status' => false,
            'assignee_name' => $task->assignee?->name,
        ] + (new PersonalTaskResource($task))->resolve($request)]);
    }

    private function eventData(Event $event): array
    {
        return ['id' => $event->id, 'title' => $event->title, 'status' => $event->status];
    }

    private function meetingData(Meeting $meeting): array
    {
        return ['id' => $meeting->id, 'title' => $meeting->title, 'scheduled_at' => $meeting->scheduled_at->toISOString()];
    }

    private function respond(array $data): JsonResponse
    {
        return response()->json($data)->header('Cache-Control', 'private, no-store');
    }
}
