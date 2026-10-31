<?php

namespace App\Http\Requests\Procurement;

use App\Models\Budget;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('manage_budgets');
    }

    public function rules(): array
    {
        return [
            'title_en' => ['required', 'string', 'max:200'],
            'title_bn' => ['nullable', 'string', 'max:200'],
            'budget_type' => ['required', 'in:'.implode(',', [Budget::TYPE_ANNUAL, Budget::TYPE_EVENT, Budget::TYPE_GENERAL_FUND])],
            'fiscal_year' => ['required', 'integer', 'min:2020', 'max:2050'],
            'event_id' => ['nullable', 'required_if:budget_type,'.Budget::TYPE_EVENT, 'exists:events,id'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.financial_account_id' => ['required', 'exists:financial_accounts,id'],
            'items.*.allocated_amount' => ['required', 'numeric', 'min:0.01'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'event_id.required_if' => __('procurement.event_required_for_event_budget'),
            'items.required' => __('procurement.at_least_one_item_required'),
            'items.min' => __('procurement.at_least_one_item_required'),
            'items.*.allocated_amount.min' => __('procurement.allocated_amount_min'),
        ];
    }
}
