<?php

namespace App\Http\Requests;

use App\Models\FinancialAccount;
use Illuminate\Foundation\Http\FormRequest;

class StoreFundTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_transfers') ?? false;
    }

    public function rules(): array
    {
        return [
            'from_account_id' => [
                'required',
                'exists:financial_accounts,id',
                'different:to_account_id',
                function ($attribute, $value, $fail) {
                    $account = FinancialAccount::find($value);
                    if ($account && $account->account_type !== FinancialAccount::TYPE_ASSET) {
                        $fail(__('finance.must_be_asset_account'));
                    }
                },
            ],
            'to_account_id' => [
                'required',
                'exists:financial_accounts,id',
                'different:from_account_id',
                function ($attribute, $value, $fail) {
                    $account = FinancialAccount::find($value);
                    if ($account && $account->account_type !== FinancialAccount::TYPE_ASSET) {
                        $fail(__('finance.must_be_asset_account'));
                    }
                },
            ],
            'transfer_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'fee_amount' => ['nullable', 'numeric', 'min:0'],
            'reference_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
