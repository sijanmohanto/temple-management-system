<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePledgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_pledges') ?? false;
    }

    public function rules(): array
    {
        return [
            'pledged_amount' => ['required', 'numeric', 'min:1'],
            'pledged_on' => ['nullable', 'date'],
            'purpose' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
