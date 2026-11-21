<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonalTaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // The controller already scopes every task to the assignee's accessible workspace.
        $source = $this->sourceLesson;
        $sourceVisible = $source && ($source->event_id === $this->event_id || $source->isInheritedBy($this->event));

        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status,
            'due_at' => $this->due_at?->toISOString(),
            'event' => ['id' => $this->event->id, 'title' => $this->event->title],
            'can_update_status' => $request->user()?->id === $this->assignee_user_id,
            'source_event_title' => $sourceVisible ? $source->event->title : null,
            'decision_summary' => $this->when(
                $this->resource->relationLoaded('decision'),
                fn () => $this->decision && $this->decision->meeting?->event_id === $this->event_id
                    ? $this->decision->summary
                    : null,
            ),
        ];
    }
}
