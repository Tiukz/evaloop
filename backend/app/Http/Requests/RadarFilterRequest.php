<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RadarFilterRequest extends FormRequest
{
    // A bad GET must redirect once to a clean URL, not back to the invalid query forever.
    protected $redirectRoute = 'events.index';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $program = Rule::exists('study_programs', 'id');
        $faculty = filter_var($this->input('faculty_id'), FILTER_VALIDATE_INT);
        if ($faculty !== false && $faculty > 0) {
            $program->where('faculty_id', $faculty);
        }

        return [
            'period' => ['bail', 'nullable', Rule::in(['all', 'upcoming', 'history'])],
            'q' => ['bail', 'nullable', 'string', 'max:120'],
            'faculty_id' => ['bail', 'nullable', 'integer', 'exists:faculties,id'],
            'study_program_id' => ['bail', 'nullable', 'integer', $program],
            'organization_id' => ['bail', 'nullable', 'integer', 'exists:organizations,id'],
            'date_from' => ['bail', 'nullable', 'date_format:Y-m-d'],
            'date_to' => ['bail', 'nullable', 'date_format:Y-m-d'],
            'page' => ['bail', 'nullable', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $validator->errors()->has('date_from') && ! $validator->errors()->has('date_to')
                && $this->filled('date_from') && $this->filled('date_to')
                && $this->input('date_to') < $this->input('date_from')) {
                $validator->errors()->add('date_to', 'Tanggal akhir tidak boleh sebelum tanggal awal.');
            }
        });
    }
}
