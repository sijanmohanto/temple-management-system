<?php

namespace App\Http\Requests\PublicPortal;

use App\Models\Complaint;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AdminComplaintReplyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        return $user->hasRole('super_admin')
            || $user->hasPermission('respond_complaints')
            || $user->hasPermission('reply_public_complaints')
            || $user->hasPermission('manage_complaints')
            || $user->hasPermission('manage_public_complaints');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'admin_response' => ['required_without:reply', 'nullable', 'string', 'min:3', 'max:5000'],
            'reply' => ['required_without:admin_response', 'nullable', 'string', 'min:3', 'max:5000'],
            'update_status' => ['nullable', 'string', 'in:under_review,replied,resolved'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $complaint = $this->route('complaint');
            if ($complaint instanceof Complaint) {
                if (in_array($complaint->status, [Complaint::STATUS_RESOLVED, Complaint::STATUS_REJECTED, Complaint::STATUS_CLOSED])) {
                    $validator->errors()->add(
                        'status',
                        __('portal.complaints.terminal_status_immutable')
                    );
                    $validator->errors()->add(
                        'complaint',
                        __('portal.complaints.terminal_status_immutable')
                    );
                }
            }
        });
    }
}
