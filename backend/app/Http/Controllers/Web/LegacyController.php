<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\LessonRequest;
use App\Models\Event;
use App\Models\Lesson;
use App\Models\Task;
use App\Support\LessonAttachmentUpload;
use App\Support\LegacyFollowUp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class LegacyController extends Controller
{
    public function index(Request $request, Event $event)
    {
        Gate::authorize('viewWorkspace', $event);
        $event->load('eventSeries');
        $search = trim($request->validate(['q' => 'nullable|string|max:120'])['q'] ?? '');
        $matchSearch = fn ($query) => $query->where(fn ($match) => $match
            ->whereLike('problem', "%{$search}%")->orWhereLike('recommendation', "%{$search}%"));
        $lessons = $event->lessons()->with(['author', 'attachments'])->when($search !== '', $matchSearch)
            ->orderByDesc('id')->paginate(10, ['*'], 'evaluations_page');
        $inheritable = Lesson::inheritedBy($event);
        $inherited = (clone $inheritable)->with(['event', 'author', 'attachments'])->when($search !== '', $matchSearch)
            ->orderByDesc('id')->paginate(10, ['*'], 'legacy_page');
        $inheritedTotal = $search === '' ? $inherited->total() : (clone $inheritable)->count();
        $evaluationsTotal = $search === '' ? $lessons->total() : $event->lessons()->count();
        $actions = $event->tasks()->whereIn('source_lesson_id', $inherited->modelKeys())->get()->keyBy('source_lesson_id');
        $activeFollowUps = $event->tasks()->whereIn('source_lesson_id', $inheritable->select('lessons.id'))
            ->whereIn('status', ['todo', 'in_progress'])->count();
        $approvedEvaluations = $event->lessons()->where('visibility', 'series')->whereNotNull('approved_at')
            ->where('approved_at', '<=', now())->count();
        $canManage = auth()->user()->can('manage', $event);
        $canApprove = auth()->user()->isOrganizationAdmin($event->organization_id)
            && $event->eventSeries?->organization_id === $event->organization_id;
        return view('legacy.index', compact(
            'event', 'lessons', 'inherited', 'actions', 'activeFollowUps', 'approvedEvaluations', 'canManage', 'canApprove',
            'search', 'inheritedTotal', 'evaluationsTotal',
        ));
    }

    public function create(Event $event)
    {
        Gate::authorize('create', [Lesson::class, $event]);
        return view('legacy.form', ['event' => $event, 'lesson' => new Lesson]);
    }

    public function edit(Event $event, Lesson $lesson)
    {
        Gate::authorize('update', $lesson);
        $lesson->load('attachments');
        return view('legacy.form', compact('event', 'lesson'));
    }

    public function store(LessonRequest $request, LessonAttachmentUpload $upload, Event $event)
    {
        Gate::authorize('create', [Lesson::class, $event]);
        $data = $request->safe()->only(['problem', 'recommendation']) + [
            'created_by' => $request->user()->id, 'visibility' => 'event', 'approved_at' => null,
        ];
        $files = $request->validated('attachments', []);
        $upload->save(fn () => $event->lessons()->create($data), $files, $request->user()->id);

        return redirect()->route('workspace.legacy', $event)->with('status', $files
            ? 'Evaluasi dan lampiran berhasil disimpan.'
            : 'Evaluasi internal berhasil disimpan.');
    }

    public function update(LessonRequest $request, Event $event, Lesson $lesson)
    {
        DB::transaction(function () use ($request, $lesson) {
            $lesson = Lesson::lockForUpdate()->findOrFail($lesson->id);
            Gate::authorize('update', $lesson);
            $lesson->update($request->safe()->only(['problem', 'recommendation']));
        });
        return redirect()->route('workspace.legacy', $event)->with('status', 'Evaluasi diperbarui.');
    }

    public function approve(Request $request, Event $event, Lesson $lesson)
    {
        Gate::authorize('approve', $lesson);
        $request->validate(['event_id' => ['prohibited'], 'lesson_id' => ['prohibited'], 'approved_at' => ['prohibited'],
            'visibility' => ['prohibited'], 'created_by' => ['prohibited'], 'problem' => ['prohibited'], 'recommendation' => ['prohibited']]);
        DB::transaction(function () use ($lesson) {
            $lesson = Lesson::lockForUpdate()->findOrFail($lesson->id);
            Gate::authorize('approve', $lesson);
            if (! $lesson->approved_at) {
                $lesson->update(['visibility' => 'series', 'approved_at' => now()]);
            }
        });
        return redirect()->route('workspace.legacy', $event)->with('status', 'Evaluasi disetujui untuk diwariskan dalam seri ini.');
    }

    public function source(Event $event, Lesson $sourceLesson)
    {
        Gate::authorize('viewForEvent', [$sourceLesson, $event]);
        $lesson = $sourceLesson->load('author', 'event', 'attachments');
        $canManage = auth()->user()->can('actOn', [$lesson, $event]);
        $action = $event->tasks()->where('source_lesson_id', $lesson->id)->first();
        return view('legacy.source', compact('event', 'lesson', 'canManage', 'action'));
    }

    public function actionForm(Event $event, Lesson $sourceLesson)
    {
        Gate::authorize('actOn', [$sourceLesson, $event]);
        $lesson = $sourceLesson->load('event');
        $memberships = $event->memberships()->with('user')->get();
        $existing = $event->tasks()->where('source_lesson_id', $lesson->id)->first();
        return view('legacy.action', compact('event', 'lesson', 'memberships', 'existing'));
    }

    public function actionStore(Request $request, Event $event, Lesson $sourceLesson, LegacyFollowUp $followUp)
    {
        [$task, $created] = $followUp->store($request, $event, $sourceLesson);
        return redirect(route('workspace.tasks', [$event, 'task' => $task->id]).'#task-'.$task->id)
            ->with('status', $created ? 'Tindak lanjut legacy berhasil dibuat.' : 'Tindak lanjut sudah ada; tidak dibuat duplikat.');
    }
}
