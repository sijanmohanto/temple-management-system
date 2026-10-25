<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFamilyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_people') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'family_name' => ['required', 'string', 'max:150'],
            'primary_contact_person_id' => ['nullable', 'exists:people,id'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'area' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
