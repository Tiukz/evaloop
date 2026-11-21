<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EventSeriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('organization'));
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:180'], 'organization_id' => ['prohibited']];
    }
}
