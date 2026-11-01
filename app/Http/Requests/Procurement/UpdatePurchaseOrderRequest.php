<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('manage_purchase_orders');
    }

    public function rules(): array
    {
        return [
            'vendor_id' => ['required', 'exists:vendors,id'],
            'event_id' => ['nullable', 'exists:events,id'],
            'budget_id' => ['nullable', 'exists:budgets,id'],
            'order_date' => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'terms_conditions' => ['nullable', 'string', 'max:3000'],
            'delivery_address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit' => ['nullable', 'string', 'max:30'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0.01'],
            'items.*.financial_account_id' => ['required', 'exists:financial_accounts,id'],
            'items.*.purchase_request_item_id' => ['nullable', 'exists:purchase_request_items,id'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'vendor_id.required' => __('procurement.vendor_required'),
            'order_date.required' => __('procurement.order_date_required'),
            'expected_delivery_date.after_or_equal' => __('procurement.delivery_date_after_order_date'),
            'items.required' => __('procurement.at_least_one_item_required'),
            'items.min' => __('procurement.at_least_one_item_required'),
            'items.*.description.required' => __('procurement.item_description_required'),
            'items.*.quantity.min' => __('procurement.quantity_min'),
            'items.*.unit_price.min' => __('procurement.unit_price_min'),
            'items.*.financial_account_id.required' => __('procurement.account_required'),
        ];
    }
}
