<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_event_categories') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name_en' => ['required', 'string', 'max:100'],
            'name_bn' => ['nullable', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', 'unique:event_categories,slug'],
            'description_en' => ['nullable', 'string'],
            'description_bn' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:active,inactive'],
        ];
    }
}
