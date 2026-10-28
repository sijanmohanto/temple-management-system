<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettleEventFundsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('close_events')
            || $this->user()?->hasPermission('manage_events')
            || ($this->user()?->hasRole('super_admin') ?? false);
    }

    public function rules(): array
    {
        return [
            'settlement_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
