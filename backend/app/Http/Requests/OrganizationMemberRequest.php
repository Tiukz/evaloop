<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrganizationMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('organization'));
    }

    public function rules(): array
    {
        $organization = $this->route('organization');
        $membership = $this->route('membership');
        $self = $membership && $membership->user_id === $this->user()->id;

        return [
            'user_id' => $membership ? ['prohibited'] : ['bail', 'required', 'integer', 'exists:users,id', Rule::unique('organization_members', 'user_id')->where('organization_id', $organization->id)],
            'role' => $self ? ['prohibited'] : ['required', Rule::in(['admin', 'member'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'joined_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'ended_at' => ['nullable', 'date_format:Y-m-d\TH:i', 'after:joined_at'],
            'organization_id' => ['prohibited'],
            'is_platform_admin' => ['prohibited'],
        ];
    }
}
