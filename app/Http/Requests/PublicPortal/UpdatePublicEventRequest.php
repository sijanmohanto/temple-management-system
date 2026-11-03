<?php

namespace App\Http\Requests\PublicPortal;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePublicEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasPermission('manage_public_events');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $eventId = $this->route('event')?->id ?? $this->event;

        return [
            'slug' => [
                'required',
                'string',
                'max:191',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('events', 'slug')->ignore($eventId),
            ],
            'short_description_en' => ['nullable', 'string', 'max:500'],
            'short_description_bn' => ['nullable', 'string', 'max:500'],
            'banner_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:3072'],
            'remove_banner' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'slug.required' => __('portal.validation_slug_required'),
            'slug.regex' => __('portal.validation_slug_format'),
            'slug.unique' => __('portal.validation_slug_unique'),
            'banner_image.image' => __('portal.validation_banner_image'),
            'banner_image.mimes' => __('portal.validation_banner_mimes'),
            'banner_image.max' => __('portal.validation_banner_max'),
        ];
    }
}
