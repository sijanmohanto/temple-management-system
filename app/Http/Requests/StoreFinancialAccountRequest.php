<?php

namespace App\Http\Requests;

use App\Models\FinancialAccount;
use Illuminate\Foundation\Http\FormRequest;

class StoreFinancialAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_accounts') ?? false;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'unique:financial_accounts,account_code'],
            'name_en' => ['required', 'string', 'max:120'],
            'name_bn' => ['nullable', 'string', 'max:120'],
            'account_type' => ['required', 'in:'.implode(',', [
                FinancialAccount::TYPE_ASSET,
                FinancialAccount::TYPE_LIABILITY,
                FinancialAccount::TYPE_EQUITY,
                FinancialAccount::TYPE_INCOME,
                FinancialAccount::TYPE_EXPENSE,
            ])],
            'normal_balance' => ['required', 'in:'.implode(',', [
                FinancialAccount::BALANCE_DEBIT,
                FinancialAccount::BALANCE_CREDIT,
            ])],
            'parent_id' => ['nullable', 'exists:financial_accounts,id'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
