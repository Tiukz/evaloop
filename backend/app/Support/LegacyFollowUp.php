<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Lesson;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class LegacyFollowUp
{
    public function store(Request $request, Event $event, Lesson $sourceLesson): array
    {
        Gate::authorize('actOn', [$sourceLesson, $event]);
        $data = $request->validate(['title' => ['required', 'string', 'max:200'],
            'assignee_user_id' => ['required', 'integer', Rule::exists('event_members', 'user_id')->where('event_id', $event->id)],
            'due_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'event_id' => ['prohibited'], 'lesson_id' => ['prohibited'], 'source_lesson_id' => ['prohibited'],
            'status' => ['prohibited'], 'decision_id' => ['prohibited'], 'created_by' => ['prohibited'], 'approved_at' => ['prohibited']]);
        $data['due_at'] = CarbonImmutable::parse($data['due_at'], 'Asia/Makassar')->utc();
        return DB::transaction(function () use ($event, $sourceLesson, $data) {
            $event = Event::lockForUpdate()->findOrFail($event->id);
            $lesson = Lesson::lockForUpdate()->findOrFail($sourceLesson->id);
            Gate::authorize('actOn', [$lesson, $event]);
            if (! $event->memberships()->where('user_id', $data['assignee_user_id'])->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['assignee_user_id' => 'PIC tidak lagi menjadi panitia event ini.']);
            }
            if ($data['due_at']->gte($event->starts_at)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['due_at' => 'Tenggat tindak lanjut harus sebelum event dimulai.']);
            }
            // All legacy task creation locks this parent; repeated/concurrent submissions reuse the first task.
            $existing = $event->tasks()->where('source_lesson_id', $lesson->id)->first();
            if ($existing) {
                return [$existing, false];
            }
            return [$event->tasks()->create($data + ['source_lesson_id' => $lesson->id, 'status' => 'todo']), true];
        });
    }
}
