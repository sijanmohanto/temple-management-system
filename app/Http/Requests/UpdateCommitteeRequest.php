<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCommitteeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_committee') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name_en' => ['required', 'string', 'max:150'],
            'name_bn' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:50', Rule::unique('committees', 'code')->ignore($this->route('committee'))],
            'description_en' => ['nullable', 'string'],
            'description_bn' => ['nullable', 'string'],
            'term_start_date' => ['nullable', 'date'],
            'term_end_date' => ['nullable', 'date', 'after_or_equal:term_start_date'],
            'status' => ['required', 'string', 'in:active,inactive,dissolved'],
        ];
    }
}
