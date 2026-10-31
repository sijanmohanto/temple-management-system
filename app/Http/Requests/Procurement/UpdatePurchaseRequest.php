<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('manage_purchase_requests');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'request_date' => ['required', 'date'],
            'requested_by' => ['nullable', 'exists:users,id'],
            'event_id' => ['nullable', 'exists:events,id'],
            'budget_id' => ['nullable', 'exists:budgets,id'],
            'justification' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit' => ['nullable', 'string', 'max:30'],
            'items.*.estimated_unit_price' => ['required', 'numeric', 'min:0.01'],
            'items.*.financial_account_id' => ['required', 'exists:financial_accounts,id'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => __('procurement.request_title_required'),
            'request_date.required' => __('procurement.request_date_required'),
            'items.required' => __('procurement.at_least_one_item_required'),
            'items.min' => __('procurement.at_least_one_item_required'),
            'items.*.description.required' => __('procurement.item_description_required'),
            'items.*.quantity.min' => __('procurement.quantity_min'),
            'items.*.estimated_unit_price.min' => __('procurement.unit_price_min'),
            'items.*.financial_account_id.required' => __('procurement.account_required'),
        ];
    }
}
