<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EventStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return match ($this->route()->getName()) {
            'workspace.publish' => $this->user()->can('publish', $event),
            'workspace.complete' => $this->user()->can('complete', $event),
            'workspace.cancel' => $this->user()->can('cancel', $event),
            default => false,
        };
    }

    public function rules(): array
    {
        // State, timestamp and identity are assigned by the trusted action, not its payload.
        return [
            'status' => ['prohibited'],
            'published_at' => ['prohibited'],
            'event_id' => ['prohibited'],
            'organization_id' => ['prohibited'],
            'user_id' => ['prohibited'],
            'is_platform_admin' => ['prohibited'],
            'starts_at' => ['prohibited'],
            'ends_at' => ['prohibited'],
            'target_program_ids' => ['prohibited'],
        ];
    }
}
