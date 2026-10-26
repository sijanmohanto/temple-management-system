<?php

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;

class ReopenEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('reopen_events') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'target_status' => [
                'required',
                'string',
                'in:'.Event::STATUS_ACTIVE.','.Event::STATUS_UPCOMING,
            ],
            'reopen_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }
}
