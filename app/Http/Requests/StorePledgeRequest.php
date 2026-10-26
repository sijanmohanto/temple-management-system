<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePledgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_pledges') ?? false;
    }

    public function rules(): array
    {
        return [
            'event_id' => ['required', 'exists:events,id'],
            'person_id' => ['nullable', 'exists:people,id', 'required_without:family_id'],
            'family_id' => ['nullable', 'exists:families,id', 'required_without:person_id'],
            'pledge_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'pledged_amount' => ['required', 'numeric', 'min:1'],
            'pledged_on' => ['required', 'date'],
            'purpose' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
