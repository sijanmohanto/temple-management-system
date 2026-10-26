<?php

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;

class AdjustClosedEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $event = $this->route('event');
        if (! ($event instanceof Event)) {
            $event = Event::find($event);
        }

        // Only closed events in Mode B can receive post-closing adjustments
        if (! ($event instanceof Event) || ! $event->allowsAuthorizedAdjustments()) {
            return false;
        }

        return $this->user()?->hasPermission('adjust_closed_events') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'adjustment_reason' => ['required', 'string', 'min:5', 'max:1000'],
            'description_en' => ['nullable', 'string'],
            'description_bn' => ['nullable', 'string'],
            'venue' => ['nullable', 'string', 'max:255'],
        ];
    }
}
