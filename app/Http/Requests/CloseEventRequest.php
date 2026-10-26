<?php

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;

class CloseEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('close_events') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'closing_mode' => [
                'required',
                'string',
                'in:'.Event::MODE_CLOSE_AND_LOCK.','.Event::MODE_CLOSE_WITH_ADJUSTMENTS,
            ],
            'closing_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }
}
