<?php

namespace App\Http\Requests\PublicPortal;

use App\Models\EventDocument;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && ($this->user()->can('manage_event_documents') || $this->user()->can('manage_public_documents'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'title_en' => ['required', 'string', 'max:255'],
            'title_bn' => ['nullable', 'string', 'max:255'],
            'document_type' => ['required', 'string', Rule::in(EventDocument::ALLOWED_TYPES)],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'description_en' => ['nullable', 'string', 'max:2000'],
            'description_bn' => ['nullable', 'string', 'max:2000'],
            'publication_status' => ['nullable', 'string', Rule::in([EventDocument::PUB_STATUS_PUBLISHED, EventDocument::PUB_STATUS_UNPUBLISHED, EventDocument::PUB_STATUS_DRAFT])],
        ];
    }

    /**
     * Custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'event_id' => __('portal.documents.event'),
            'title_en' => __('portal.documents.title_en'),
            'title_bn' => __('portal.documents.title_bn'),
            'document_type' => __('portal.documents.type'),
            'file' => __('portal.documents.file'),
            'description_en' => __('portal.documents.description_en'),
            'description_bn' => __('portal.documents.description_bn'),
        ];
    }
}
