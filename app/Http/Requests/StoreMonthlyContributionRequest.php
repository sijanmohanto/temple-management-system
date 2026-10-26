<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMonthlyContributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_committee_contributions') ?? false;
    }

    public function rules(): array
    {
        return [
            'committee_membership_id' => ['required', 'exists:committee_memberships,id'],
            'contribution_month' => ['required', 'date'],
            'expected_amount' => ['required', 'numeric', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
