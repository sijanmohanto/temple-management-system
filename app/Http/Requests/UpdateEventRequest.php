<?php

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
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

        // Normal CRUD updates are strictly forbidden on closed events
        if ($event instanceof Event && $event->isClosed()) {
            return false;
        }

        return $this->user()?->hasPermission('manage_events') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name_en' => ['required', 'string', 'max:150'],
            'name_bn' => ['nullable', 'string', 'max:150'],
            'event_category_id' => ['required', 'exists:event_categories,id'],
            'event_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'description_en' => ['nullable', 'string'],
            'description_bn' => ['nullable', 'string'],
            'venue' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'collection_start_date' => ['nullable', 'date'],
            'collection_end_date' => [
                'nullable',
                'date',
                'required_with:collection_start_date',
                'after_or_equal:collection_start_date',
            ],
            'status' => [
                'required',
                'string',
                'in:'.Event::STATUS_DRAFT.','.Event::STATUS_UPCOMING.','.Event::STATUS_ACTIVE.','.Event::STATUS_CLOSING,
            ],
        ];
    }
}
