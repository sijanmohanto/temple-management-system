<?php

namespace App\Http\Requests\PublicPortal;

use App\Models\ReceiverAccount;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReceiverAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('manage_receiver_accounts');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'string', Rule::in(ReceiverAccount::ALLOWED_METHODS)],
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:100'],
            'account_type' => ['required', 'string', Rule::in(ReceiverAccount::ALLOWED_TYPES)],
            'bank_name' => ['required_if:payment_method,bank_transfer', 'nullable', 'string', 'max:255'],
            'branch_name' => ['nullable', 'string', 'max:255'],
            'routing_number' => ['nullable', 'string', 'max:100'],
            'swift_code' => ['nullable', 'string', 'max:50'],
            'qr_code' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'remove_qr_code' => ['nullable', 'boolean'],
            'instructions_en' => ['nullable', 'string', 'max:2000'],
            'instructions_bn' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
            'is_verified' => ['nullable', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'financial_account_id' => ['nullable', 'integer', 'exists:financial_accounts,id'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
        ];
    }

    /**
     * Custom attribute names for validation.
     */
    public function attributes(): array
    {
        return [
            'payment_method' => __('portal.receivers.payment_method'),
            'account_name' => __('portal.receivers.account_name'),
            'account_number' => __('portal.receivers.account_number'),
            'account_type' => __('portal.receivers.account_type'),
            'bank_name' => __('portal.receivers.bank_name'),
            'branch_name' => __('portal.receivers.branch_name'),
            'routing_number' => __('portal.receivers.routing_number'),
            'swift_code' => __('portal.receivers.swift_code'),
            'qr_code' => __('portal.receivers.qr_code'),
            'instructions_en' => __('portal.receivers.instructions_en'),
            'instructions_bn' => __('portal.receivers.instructions_bn'),
            'display_order' => __('portal.receivers.display_order'),
            'financial_account_id' => __('portal.receivers.financial_account'),
        ];
    }
}
