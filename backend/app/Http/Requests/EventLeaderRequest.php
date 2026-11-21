<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EventLeaderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assignLeader', $this->route('event'));
    }

    public function rules(): array
    {
        return ['user_id' => ['required', 'integer', 'exists:users,id'], 'role' => ['prohibited'], 'event_id' => ['prohibited']];
    }
}
