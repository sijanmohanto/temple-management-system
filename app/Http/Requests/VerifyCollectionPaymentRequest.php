<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyCollectionPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('verify_collections') ?? false;
    }

    public function rules(): array
    {
        return [
            'verification_note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
