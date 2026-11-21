<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;

class LegacyController extends Controller
{
    public function index(Request $request, Event $event): JsonResponse
    {
        Gate::authorize('viewWorkspace', $event);
        $filters = $request->validate(['q' => 'nullable|string|max:120', 'page' => 'nullable|integer|min:1']);
        $event->loadMissing('eventSeries:id,organization_id,name');
        $search = trim($filters['q'] ?? '');
        $inherited = Lesson::inheritedBy($event);
        $lessons = (clone $inherited)->with(['event', 'author:id,name'])
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $match) => $match
                ->whereLike('problem', "%{$search}%")->orWhereLike('recommendation', "%{$search}%")))
            ->orderByDesc('id')->paginate(10);
        $actions = $event->tasks()->whereIn('source_lesson_id', $lessons->getCollection()->modelKeys())
            ->orderBy('id')->get()->groupBy('source_lesson_id');
        $userId = $request->user()->id;

        return $this->respond([
            'event' => $this->eventData($event) + ['series' => $event->eventSeries?->name],
            'data' => $lessons->getCollection()->map(fn (Lesson $lesson) =>
                $this->lessonData($lesson, $actions->get($lesson->id)?->first(), $userId)),
            'meta' => $this->pagination($lessons),
            'summary' => [
                'inherited' => $search === '' ? $lessons->total() : (clone $inherited)->count(),
                'active_follow_ups' => $event->tasks()
                    ->whereIn('source_lesson_id', $inherited->select('lessons.id'))
                    ->whereIn('status', ['todo', 'in_progress'])->count(),
            ],
        ]);
    }

    public function show(Request $request, Event $event, int $lesson): JsonResponse
    {
        Gate::authorize('viewWorkspace', $event);
        $lesson = Lesson::inheritedBy($event)
            ->with(['event', 'author:id,name', 'attachments' => fn ($files) => $files
                ->whereIn('mime_type', [LessonAttachment::MIME_TYPES['jpg'], LessonAttachment::MIME_TYPES['png'], LessonAttachment::MIME_TYPES['webp']])
                ->select(['id', 'lesson_id', 'original_name', 'size_bytes'])])
            ->findOrFail($lesson);
        Gate::authorize('viewForEvent', [$lesson, $event]);

        $action = $event->tasks()->where('source_lesson_id', $lesson->id)->orderBy('id')->first();

        return $this->respond(['data' => $this->lessonData($lesson, $action, $request->user()->id) + [
            'images' => $lesson->attachments->map(fn (LessonAttachment $file) => [
                'id' => $file->id,
                'name' => $file->original_name,
                'size_bytes' => (int) $file->size_bytes,
            ]),
        ]]);
    }

    private function eventData(Event $event): array
    {
        return ['id' => $event->id, 'title' => $event->title, 'starts_at' => $event->starts_at->toISOString()];
    }

    private function lessonData(Lesson $lesson, ?Task $action, int $userId): array
    {
        return [
            'id' => $lesson->id,
            'problem' => $lesson->problem,
            'recommendation' => $lesson->recommendation,
            'source_event' => $this->eventData($lesson->event),
            'author' => $lesson->author->name,
            'approved_at' => $lesson->approved_at->toISOString(),
            'follow_up' => $action ? [
                'title' => $action->title,
                'status' => $action->status,
                'due_at' => $action->due_at?->toISOString(),
                // Workspace readers can see the summary; the personal Task API checks the PIC again.
                'personal_task_id' => $action->assignee_user_id === $userId ? $action->id : null,
            ] : null,
        ];
    }

    private function pagination(LengthAwarePaginator $page): array
    {
        return ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage()];
    }

    private function respond(array $data): JsonResponse
    {
        return response()->json($data)->header('Cache-Control', 'private, no-store');
    }
}
