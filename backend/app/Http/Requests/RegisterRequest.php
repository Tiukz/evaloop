<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => strtolower(trim($this->input('email')))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:128', 'confirmed', function (string $attribute, mixed $value, \Closure $fail): void {
                if (is_string($value) && strlen($value) > 72) {
                    $fail('Password terlalu panjang. Gunakan kombinasi yang lebih pendek (maksimal 72 byte).');
                }
            }],
            'role' => ['prohibited'],
            'is_platform_admin' => ['prohibited'],
            'university_id' => ['prohibited'],
            'organization_id' => ['prohibited'],
        ];
    }
}
