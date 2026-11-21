<?php

namespace App\Http\Requests;

use App\Models\Lesson;
use Illuminate\Foundation\Http\FormRequest;

class LessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('lesson')
            ? $this->user()->can('update', $this->route('lesson'))
            : $this->user()->can('create', [Lesson::class, $this->route('event')]);
    }

    public function rules(): array
    {
        return ['problem' => ['required', 'string', 'max:15000'], 'recommendation' => ['required', 'string', 'max:15000'],
            'event_id' => ['prohibited'], 'lesson_id' => ['prohibited'], 'source_lesson_id' => ['prohibited'],
            'created_by' => ['prohibited'], 'approved_at' => ['prohibited'], 'visibility' => ['prohibited']]
            + ($this->route('lesson') ? [] : LessonAttachmentRequest::attachmentRules(required: false));
    }

    public function messages(): array
    {
        return LessonAttachmentRequest::attachmentMessages();
    }
}
