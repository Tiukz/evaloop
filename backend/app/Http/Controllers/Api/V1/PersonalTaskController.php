<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PersonalTaskResource;
use App\Models\Event;
use App\Models\Task;
use App\Support\PersonalTasks;
use App\Support\TaskProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PersonalTaskController extends Controller
{
    public function index(Request $request, PersonalTasks $personalTasks): JsonResponse
    {
        $filters = $request->validate([
            'view' => ['nullable', Rule::in(['all', 'active', 'done'])],
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['todo', 'in_progress', 'done'])],
            'event' => ['nullable', 'integer', 'min:1'],
            'sort' => ['nullable', Rule::in(['deadline_asc', 'deadline_desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $query = $personalTasks->query($request->user());
        $filterEvents = Event::whereIn('id', (clone $query)->select('tasks.event_id'))
            ->orderBy('title')->orderBy('id')->get(['id', 'title']);

        if (($filters['view'] ?? null) === 'active') $query->where('status', '!=', 'done');
        if (($filters['view'] ?? null) === 'done') $query->where('status', 'done');
        if (($filters['q'] ?? '') !== '') $query->where('title', 'like', '%'.$filters['q'].'%');
        if (isset($filters['status'])) $query->where('status', $filters['status']);
        if (isset($filters['event'])) $query->where('event_id', $filters['event']);

        $tasks = $query->with(['event.eventSeries', 'sourceLesson.event'])
            ->orderByRaw('due_at IS NULL')
            ->orderBy('due_at', ($filters['sort'] ?? null) === 'deadline_desc' ? 'desc' : 'asc')
            ->orderBy('id')->paginate(20)->appends($filters);

        return PersonalTaskResource::collection($tasks)->additional([
            'task_filters' => ['events' => $filterEvents],
        ])->response()->header('Cache-Control', 'private, no-store');
    }

    public function show(Request $request, PersonalTasks $personalTasks, int $task): JsonResponse
    {
        return $this->respond($this->find($request, $personalTasks, $task));
    }

    public function updateStatus(Request $request, PersonalTasks $personalTasks, TaskProgress $progress, int $task): JsonResponse
    {
        $task = $this->find($request, $personalTasks, $task);

        return $this->respond($progress->update($request, $task->event, $task, personalOnly: true));
    }

    private function find(Request $request, PersonalTasks $personalTasks, int $task): Task
    {
        return $personalTasks->query($request->user())->with('event')->findOrFail($task);
    }

    private function respond(Task $task): JsonResponse
    {
        $task->loadMissing(['event.eventSeries', 'sourceLesson.event', 'decision.meeting']);

        return (new PersonalTaskResource($task))->response()->header('Cache-Control', 'private, no-store');
    }
}
