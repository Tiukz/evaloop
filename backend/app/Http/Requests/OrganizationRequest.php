<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('university'));
    }

    public function rules(): array
    {
        $university = $this->route('university');
        $facultyId = filter_var($this->input('faculty_id'), FILTER_VALIDATE_INT) ?: 0;

        return [
            'name' => ['required', 'string', 'max:160'],
            'faculty_id' => ['bail', 'nullable', 'required_with:study_program_id', 'integer', Rule::exists('faculties', 'id')->where('university_id', $university->id)],
            'study_program_id' => ['bail', 'nullable', 'integer', Rule::exists('study_programs', 'id')->where('faculty_id', $facultyId)],
            'initial_admin_user_id' => $this->isMethod('POST') ? ['required', 'integer', 'exists:users,id'] : ['prohibited'],
            'university_id' => ['prohibited'],
            'is_platform_admin' => ['prohibited'],
            'status' => ['prohibited'], // No organization status column in the current PDM.
        ];
    }
}
