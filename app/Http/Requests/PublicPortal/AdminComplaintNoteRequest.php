<?php

namespace App\Http\Requests\PublicPortal;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AdminComplaintNoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        return $user->hasRole('super_admin')
            || $user->hasPermission('manage_complaints')
            || $user->hasPermission('manage_public_complaints')
            || $user->hasPermission('view_complaints')
            || $user->hasPermission('view_public_complaints');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'internal_notes' => ['required_without:note', 'nullable', 'string', 'min:3', 'max:5000'],
            'note' => ['required_without:internal_notes', 'nullable', 'string', 'min:3', 'max:5000'],
        ];
    }
}
