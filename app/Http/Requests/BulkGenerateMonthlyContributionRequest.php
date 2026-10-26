<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkGenerateMonthlyContributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_committee_contributions') ?? false;
    }

    public function rules(): array
    {
        return [
            'committee_id' => ['required', 'exists:committees,id'],
            'contribution_month' => ['required', 'date'],
            'default_expected_amount' => ['required', 'numeric', 'min:1'],
        ];
    }
}
