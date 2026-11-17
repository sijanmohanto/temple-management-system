<?php

namespace App\Http\Requests\PublicPortal;

use App\Models\Complaint;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AdminComplaintStatusRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('status')) {
            $routeName = $this->route()?->getName();
            if ($routeName === 'portal.complaints.resolve') {
                $this->merge(['status' => Complaint::STATUS_RESOLVED]);
            } elseif ($routeName === 'portal.complaints.reject') {
                $this->merge(['status' => Complaint::STATUS_REJECTED]);
            } elseif ($routeName === 'portal.complaints.close') {
                $this->merge(['status' => Complaint::STATUS_CLOSED]);
            } elseif ($routeName === 'portal.complaints.under-review') {
                $this->merge(['status' => Complaint::STATUS_UNDER_REVIEW]);
            }
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        $targetStatus = $this->input('status');

        if (in_array($targetStatus, [Complaint::STATUS_RESOLVED, Complaint::STATUS_REJECTED, Complaint::STATUS_CLOSED])) {
            return $user->hasRole('super_admin')
                || $user->hasPermission('resolve_complaints')
                || $user->hasPermission('resolve_public_complaints')
                || $user->hasPermission('manage_complaints')
                || $user->hasPermission('manage_public_complaints');
        }

        return $user->hasRole('super_admin')
            || $user->hasPermission('manage_complaints')
            || $user->hasPermission('manage_public_complaints')
            || $user->hasPermission('respond_complaints')
            || $user->hasPermission('reply_public_complaints');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:under_review,replied,resolved,rejected,closed'],
            'note' => ['nullable', 'string', 'max:5000'],
            'rejection_reason' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $complaint = $this->route('complaint');
            $newStatus = $this->input('status');

            if ($complaint instanceof Complaint) {
                // Terminal status immutability: resolved/rejected/closed cannot silently change
                if (in_array($complaint->status, [Complaint::STATUS_RESOLVED, Complaint::STATUS_REJECTED, Complaint::STATUS_CLOSED])) {
                    if ($complaint->status !== $newStatus) {
                        $validator->errors()->add(
                            'status',
                            __('portal.complaints.terminal_status_immutable')
                        );
                    }
                }
            }

            // Mandatory reasons
            if ($newStatus === Complaint::STATUS_REJECTED) {
                $reason = trim((string) ($this->input('rejection_reason') ?: $this->input('note')));
                if (strlen($reason) < 3) {
                    $validator->errors()->add('rejection_reason', __('portal.complaints.rejection_reason_required'));
                }
            }

            if ($newStatus === Complaint::STATUS_RESOLVED) {
                $note = trim((string) ($this->input('note') ?: $this->input('admin_response')));
                if (strlen($note) < 3) {
                    $validator->errors()->add('note', __('portal.complaints.resolution_note_required'));
                }
            }
        });
    }
}
