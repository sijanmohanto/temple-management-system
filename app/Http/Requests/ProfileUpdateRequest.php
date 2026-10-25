<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'string',
                'email',
                'max:150',
                Rule::unique('users')->ignore($this->user()->id),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'preferred_locale' => ['required', 'string', 'in:en,bn'],
            'address' => ['nullable', 'string', 'max:500'],
        ];
    }
}
