<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TaskProgress
{
    public function update(Request $request, Event $event, Task $task, bool $personalOnly = false): Task
    {
        Gate::authorize('viewWorkspace', $event);

        return DB::transaction(function () use ($request, $event, $task, $personalOnly) {
            $event = Event::lockForUpdate()->findOrFail($event->id);
            Gate::authorize('viewWorkspace', $event);
            $task = $event->tasks()->lockForUpdate()->findOrFail($task->id);
            $user = $request->user();
            $isAssignee = $task->assignee_user_id === $user->id;

            // A personal task may have been reassigned after it was selected.
            abort_if($personalOnly && ! $isAssignee, 404);
            abort_unless($user->can('manage', $event) || $isAssignee, 403);

            if ($personalOnly) {
                $unexpected = array_diff(array_keys($request->all()), ['status', '_token']);
                if ($unexpected !== []) {
                    throw ValidationException::withMessages(array_fill_keys($unexpected, 'Field ini tidak boleh dikirim.'));
                }
            }

            $data = $request->validate([
                'status' => ['required', Rule::in(['todo', 'in_progress', 'done'])],
                'assignee_user_id' => ['prohibited'], 'event_id' => ['prohibited'], 'title' => ['prohibited'],
            ]);
            $task->update(['status' => $data['status']]);

            return $task->setRelation('event', $event);
        });
    }
}
