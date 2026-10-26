<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReverseCollectionPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('reverse_collections') ?? false;
    }

    public function rules(): array
    {
        return [
            'reversal_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }
}
