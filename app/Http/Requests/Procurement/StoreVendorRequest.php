<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('manage_vendors');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
            'contact_person' => is_string($this->contact_person) && trim($this->contact_person) !== '' ? trim($this->contact_person) : null,
            'phone' => is_string($this->phone) && trim($this->phone) !== '' ? trim($this->phone) : null,
            'email' => is_string($this->email) && trim($this->email) !== '' ? trim($this->email) : null,
            'tax_id' => is_string($this->tax_id) && trim($this->tax_id) !== '' ? trim($this->tax_id) : null,
            'vendor_type' => is_string($this->vendor_type) && trim($this->vendor_type) !== '' ? trim($this->vendor_type) : null,
            'address' => is_string($this->address) && trim($this->address) !== '' ? trim($this->address) : null,
            'notes' => is_string($this->notes) && trim($this->notes) !== '' ? trim($this->notes) : null,
            'is_active' => $this->boolean('is_active', true),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'phone' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('vendors', 'phone')->whereNull('deleted_at'),
            ],
            'email' => ['nullable', 'email', 'max:100'],
            'vendor_type' => ['nullable', 'string', 'max:60'],
            'tax_id' => [
                'nullable',
                'string',
                'max:60',
                Rule::unique('vendors', 'tax_id')->whereNull('deleted_at'),
            ],
            'address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('procurement.vendor_name_required'),
            'phone.unique' => __('procurement.vendor_phone_unique'),
            'tax_id.unique' => __('procurement.vendor_tax_id_unique'),
            'email.email' => __('procurement.vendor_email_invalid'),
        ];
    }
}
