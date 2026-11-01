<?php

namespace App\Http\Requests\Procurement;

use App\Models\FinancialAccount;
use App\Models\VendorPayment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVendorPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('manage_vendor_payments') || $this->user()->hasRole('super_admin');
    }

    public function rules(): array
    {
        return [
            'vendor_id' => [
                'required',
                Rule::exists('vendors', 'id')->where('is_active', true),
            ],
            'financial_account_id' => [
                'required',
                Rule::exists('financial_accounts', 'id')
                    ->where('account_type', FinancialAccount::TYPE_ASSET)
                    ->where('is_active', true),
            ],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', Rule::in(array_keys(VendorPayment::supportedPaymentMethods()))],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.vendor_bill_id' => ['required', 'exists:vendor_bills,id'],
            'allocations.*.allocated_amount' => ['required', 'numeric', 'min:0.01'],
            'allocations.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'vendor_id.required' => __('procurement.vendor_required'),
            'financial_account_id.required' => __('procurement.payment_account_required'),
            'payment_date.required' => __('procurement.payment_date_required'),
            'payment_method.required' => __('procurement.payment_method_required'),
            'amount.required' => __('procurement.payment_amount_required'),
            'amount.min' => __('procurement.payment_amount_min'),
            'allocations.required' => __('procurement.at_least_one_bill_allocation_required'),
            'allocations.min' => __('procurement.at_least_one_bill_allocation_required'),
            'allocations.*.vendor_bill_id.required' => __('procurement.bill_required'),
            'allocations.*.allocated_amount.required' => __('procurement.allocation_amount_required'),
            'allocations.*.allocated_amount.min' => __('procurement.allocation_amount_min'),
            'document.mimes' => __('procurement.document_mimes_invalid'),
            'document.max' => __('procurement.document_size_exceeded'),
        ];
    }
}
