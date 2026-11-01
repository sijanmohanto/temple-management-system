<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVendorBillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('manage_vendor_bills') || $this->user()->hasRole('super_admin');
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('items') && is_array($this->items)) {
            $items = $this->items;
            foreach ($items as $idx => $item) {
                if (isset($item['item_description']) && ! isset($item['description'])) {
                    $items[$idx]['description'] = $item['item_description'];
                }
            }
            $this->merge(['items' => $items]);
        }
    }

    public function rules(): array
    {
        return [
            'vendor_id' => ['required', Rule::exists('vendors', 'id')->where('is_active', true)],
            'purchase_order_id' => ['nullable', 'exists:purchase_orders,id'],
            'event_id' => ['nullable', 'exists:events,id'],
            'budget_id' => ['nullable', 'exists:budgets,id'],
            'vendor_invoice_number' => ['required', 'string', 'max:100'],
            'bill_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:bill_date'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit' => ['nullable', 'string', 'max:30'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.financial_account_id' => ['required', 'exists:financial_accounts,id'],
            'items.*.purchase_order_item_id' => ['nullable', 'exists:purchase_order_items,id'],
            'items.*.budget_item_id' => ['nullable', 'exists:budget_items,id'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'vendor_id.required' => __('procurement.vendor_required'),
            'vendor_invoice_number.required' => __('procurement.vendor_invoice_number_required'),
            'bill_date.required' => __('procurement.bill_date_required'),
            'due_date.after_or_equal' => __('procurement.due_date_after_bill_date'),
            'items.required' => __('procurement.at_least_one_item_required'),
            'items.min' => __('procurement.at_least_one_item_required'),
            'items.*.description.required' => __('procurement.item_description_required'),
            'items.*.quantity.min' => __('procurement.quantity_min'),
            'items.*.unit_price.min' => __('procurement.unit_price_min'),
            'items.*.financial_account_id.required' => __('procurement.account_required'),
            'document.mimes' => __('procurement.document_mimes_invalid'),
            'document.max' => __('procurement.document_size_exceeded'),
        ];
    }
}
