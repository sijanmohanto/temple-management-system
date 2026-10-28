<?php

namespace App\Http\Requests;

use App\Models\FinancialAccount;
use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_expenses') || $this->user()?->can('record_expenses');
    }

    public function rules(): array
    {
        return [
            'title_en' => ['required', 'string', 'max:200'],
            'title_bn' => ['nullable', 'string', 'max:200'],
            'expense_account_id' => [
                'required',
                'exists:financial_accounts,id',
                function ($attribute, $value, $fail) {
                    $account = FinancialAccount::find($value);
                    if ($account && $account->account_type !== FinancialAccount::TYPE_EXPENSE) {
                        $fail(__('finance.must_be_expense_account'));
                    }
                },
            ],
            'payment_account_id' => [
                'required',
                'exists:financial_accounts,id',
                function ($attribute, $value, $fail) {
                    $account = FinancialAccount::find($value);
                    if ($account && $account->account_type !== FinancialAccount::TYPE_ASSET) {
                        $fail(__('finance.must_be_asset_account'));
                    }
                },
            ],
            'expense_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:cash,bank_transfer,cheque,bkash,nagad,rocket,upay'],
            'vendor_name' => ['nullable', 'string', 'max:150'],
            'vendor_phone' => ['nullable', 'string', 'max:30'],
            'invoice_number' => ['nullable', 'string', 'max:50'],
            'event_id' => ['nullable', 'exists:events,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
