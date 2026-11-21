<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'organization' => [
                'id' => $this->organization->id,
                'name' => $this->organization->name,
                'faculty' => $this->organization->faculty?->name,
                'study_program' => $this->organization->studyProgram?->name,
            ],
            'starts_at' => $this->starts_at->toISOString(),
            'ends_at' => $this->ends_at->toISOString(),
            'venue' => $this->venue,
            'status' => $this->status,
            'published_at' => $this->published_at->toISOString(),
            'poster_url' => $this->poster_path ? route('events.media.poster', $this->id) : null,
        ];
    }
}
