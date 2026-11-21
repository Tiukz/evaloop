<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $membership = $this->route('membership');

        return $this->user()->can('manage', $this->route('event'))
            && (! $membership || $membership->role !== 'coordinator');
    }

    public function rules(): array
    {
        $membership = $this->route('membership');

        return [
            'user_id' => $membership ? ['prohibited'] : ['bail', 'required', 'integer', 'exists:users,id', Rule::unique('event_members', 'user_id')->where('event_id', $this->route('event')->id)],
            'role' => ['required', Rule::in(['member'])], // Coordinator assignment is a separate organization-admin action.
            'event_id' => ['prohibited'],
            'organization_id' => ['prohibited'],
            'is_platform_admin' => ['prohibited'],
        ];
    }
}
