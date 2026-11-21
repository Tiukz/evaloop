<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Task;
use App\Models\Decision;
use App\Support\PersonalTasks;
use App\Support\TaskProgress;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request, Event $event)
    {
        Gate::authorize('viewWorkspace', $event);
        $event->load('eventSeries');
        $perPage = 20;
        $taskId = filter_var($request->query('task'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $page = $taskId && $event->tasks()->whereKey($taskId)->exists()
            ? intdiv($event->tasks()->where('id', '<', $taskId)->count(), $perPage) + 1
            : null;
        $tasks = $event->tasks()->with(['assignee', 'decision', 'sourceLesson.event'])->orderBy('id')
            ->paginate($perPage, page: $page);
        $tasks->each(fn ($task) => $task->setRelation('event', $event));
        $canManage = auth()->user()->can('manage', $event);
        $counts = $event->tasks()->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');
        $overdue = $event->tasks()->where('status', '!=', 'done')->where('due_at', '<', now())->count();
        return view('tasks.index', compact('event', 'tasks', 'counts', 'overdue', 'canManage'));
    }

    public function mine(Request $request, PersonalTasks $personalTasks)
    {
        $filters = $request->validate([
            'view' => ['nullable', Rule::in(['all', 'active', 'done'])],
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['todo', 'in_progress', 'done'])],
            'event' => ['nullable', 'integer', 'min:1'],
            'sort' => ['nullable', Rule::in(['deadline_asc', 'deadline_desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $filters = array_merge(['view' => 'all', 'q' => '', 'status' => '', 'event' => '', 'sort' => 'deadline_asc'], array_filter($filters, fn ($value) => $value !== null));
        $user = $request->user();
        $query = $personalTasks->query($user);
        $filterEvents = Event::whereIn('id', (clone $query)->select('tasks.event_id'))->orderBy('title')->get(['id', 'title']);
        if ($filters['view'] === 'active') $query->where('status', '!=', 'done');
        if ($filters['view'] === 'done') $query->where('status', 'done');
        if ($filters['q'] !== '') $query->where('title', 'like', '%'.$filters['q'].'%');
        if ($filters['status'] !== '') $query->where('status', $filters['status']);
        if ($filters['event'] !== '') $query->where('event_id', $filters['event']);
        $tasks = $query->with(['event.eventSeries', 'assignee', 'sourceLesson.event'])
            ->orderByRaw('due_at IS NULL')->orderBy('due_at', $filters['sort'] === 'deadline_desc' ? 'desc' : 'asc')
            ->orderBy('id')->paginate(20)->withQueryString();
        $adminOrgIds = $user->organizationMemberships()->active()->where('role', 'admin')->pluck('organization_id')->all();
        $leaderEventIds = $user->eventMemberships()->where('role', 'coordinator')->pluck('event_id')->all();
        $managedEventIds = $tasks->filter(fn ($task) => in_array($task->event->organization_id, $adminOrgIds) || in_array($task->event_id, $leaderEventIds))->pluck('event_id')->all();
        return view('dashboard.tasks', compact('tasks', 'managedEventIds', 'filters', 'filterEvents'));
    }

    private function form(Event $event, Task $task)
    {
        $event->load('eventSeries');
        $task->load('sourceLesson.event');
        $memberships = $event->memberships()->with('user')->get();
        $decisions = Decision::whereHas('meeting', fn ($q) => $q->where('event_id', $event->id))->with('meeting')->get();
        return view('tasks.form', compact('event', 'task', 'memberships', 'decisions'));
    }

    public function create(Event $event)
    {
        Gate::authorize('manage', $event);
        return $this->form($event, new Task);
    }

    public function edit(Event $event, Task $task)
    {
        Gate::authorize('manage', $event);
        return $this->form($event, $task);
    }

    private function data(Request $request, Event $event): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'status' => ['required', Rule::in(['todo', 'in_progress', 'done'])],
            'assignee_user_id' => ['nullable', 'integer', Rule::exists('event_members', 'user_id')->where('event_id', $event->id)],
            'decision_id' => ['nullable', 'integer', Rule::exists('decisions', 'id')->whereIn('meeting_id', $event->meetings()->select('id'))],
            'due_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'event_id' => ['prohibited'], 'source_lesson_id' => ['prohibited'],
        ]);
        $data['due_at'] = ! empty($data['due_at']) ? CarbonImmutable::parse($data['due_at'], 'Asia/Makassar')->utc() : null;
        return $data;
    }

    public function store(Request $request, Event $event)
    {
        Gate::authorize('manage', $event);
        DB::transaction(function () use ($request, $event) {
            $event = Event::lockForUpdate()->findOrFail($event->id);
            Gate::authorize('manage', $event);
            $event->tasks()->create($this->data($request, $event));
        });
        return redirect()->route('workspace.tasks', $event)->with('status', 'Tugas berhasil dibuat.');
    }

    public function update(Request $request, Event $event, Task $task)
    {
        Gate::authorize('manage', $event);
        DB::transaction(function () use ($request, $event, $task) {
            $event = Event::lockForUpdate()->findOrFail($event->id);
            Gate::authorize('manage', $event);
            $task = $event->tasks()->lockForUpdate()->findOrFail($task->id);
            $task->update($this->data($request, $event));
        });
        return redirect()->route('workspace.tasks', $event)->with('status', 'Tugas berhasil diperbarui.');
    }

    public function status(Request $request, Event $event, Task $task, TaskProgress $progress)
    {
        $progress->update($request, $event, $task);
        return back()->with('status', 'Progres tugas diperbarui.');
    }
}
