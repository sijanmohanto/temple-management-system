<?php

namespace App\Http\Requests;

use App\Models\CollectionPayment;
use Illuminate\Foundation\Http\FormRequest;

class StoreCollectionPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('record_collections') ?? false;
    }

    public function rules(): array
    {
        return [
            'collection_type' => [
                'required',
                'string',
                'in:'.implode(',', [
                    CollectionPayment::TYPE_EVENT_PLEDGE,
                    CollectionPayment::TYPE_EVENT_DONATION,
                    CollectionPayment::TYPE_MONTHLY_CONTRIBUTION,
                    CollectionPayment::TYPE_GENERAL_DONATION,
                ]),
            ],
            'payer_person_id' => ['nullable', 'exists:people,id'],
            'payer_family_id' => ['nullable', 'exists:families,id'],
            'payer_name_override' => ['nullable', 'string', 'max:150'],
            'payer_phone_override' => ['nullable', 'string', 'max:30'],
            'event_id' => ['nullable', 'exists:events,id'],
            'pledge_id' => ['nullable', 'exists:pledges,id'],
            'monthly_contribution_id' => ['nullable', 'exists:monthly_committee_contributions,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_date' => ['required', 'date'],
            'payment_method' => [
                'required',
                'string',
                'in:'.implode(',', [
                    CollectionPayment::METHOD_CASH,
                    CollectionPayment::METHOD_BANK,
                    CollectionPayment::METHOD_BKASH,
                    CollectionPayment::METHOD_NAGAD,
                    CollectionPayment::METHOD_ROCKET,
                    CollectionPayment::METHOD_CHEQUE,
                    CollectionPayment::METHOD_OTHER,
                ]),
            ],
            'transaction_reference' => ['nullable', 'string', 'max:150'],
            'bank_name' => ['nullable', 'string', 'max:150'],
            'cheque_number' => ['nullable', 'string', 'max:100'],
            'payment_notes' => ['nullable', 'string'],
            'is_direct_cash_entry' => ['nullable', 'boolean'],
            'allocations' => ['nullable', 'array'],
            'allocations.*.target_type' => ['required_with:allocations', 'string'],
            'allocations.*.amount' => ['required_with:allocations', 'numeric', 'min:0.01'],
        ];
    }
}
