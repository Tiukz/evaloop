<?php

namespace App\Http\Requests;

use App\Models\Event;
use App\Support\WorkspaceImage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('event')
            ? $this->user()->can('manage', $this->route('event'))
            : $this->user()->can('create', [Event::class, $this->route('organization')]);
    }

    public function rules(): array
    {
        $event = $this->route('event');
        $organization = $event?->organization ?? $this->route('organization');

        return [
            'title' => ['required', 'string', 'max:200'],
            'event_series_id' => ['bail', 'nullable', 'integer', Rule::exists('event_series', 'id')->where('organization_id', $organization->id)],
            'description' => ['nullable', 'string', 'max:20000', function (string $attribute, mixed $value, \Closure $fail): void {
                // MySQL TEXT capacity is in bytes, not Unicode characters.
                if (is_string($value) && strlen($value) > 65535) {
                    $fail('Deskripsi terlalu panjang untuk disimpan.');
                }
            }],
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'ends_at' => ['required', 'date_format:Y-m-d\TH:i', 'after:starts_at'],
            'venue' => ['nullable', 'string', 'max:160'],
            'poster' => ['nullable', ...WorkspaceImage::RULES],
            'target_program_ids' => ['sometimes', 'array'],
            'target_program_ids.*' => ['bail', 'integer', 'distinct', Rule::exists('study_programs', 'id')->where(fn ($query) => $query->whereIn('faculty_id', DB::table('faculties')->select('id')->where('university_id', $organization->university_id)))],
            'organization_id' => ['prohibited'],
            'status' => ['prohibited'], // Draft/publication/other transitions must be set by trusted actions.
            'published_at' => ['prohibited'],
            'is_platform_admin' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'poster.image' => 'Pilih gambar JPG, PNG, atau WebP.',
            'poster.mimes' => 'Pilih gambar JPG, PNG, atau WebP.',
            'poster.max' => 'Ukuran poster maksimal 2 MB.',
            'poster.dimensions' => 'Dimensi poster maksimal 4096 × 4096 piksel.',
        ];
    }
}
