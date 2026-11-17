<?php

namespace App\Http\Requests\PublicPortal;

use App\Models\CollectionPayment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class RejectPublicPaymentRequest extends FormRequest
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
            || $user->hasPermission('reject_public_payments')
            || $user->hasPermission('verify_public_payments');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }

    /**
     * Resolve the target CollectionPayment model instance.
     */
    public function getTargetPayment(): ?CollectionPayment
    {
        $payment = $this->route('payment');
        if ($payment instanceof CollectionPayment) {
            return $payment;
        }

        if (is_numeric($payment)) {
            return CollectionPayment::find($payment);
        }

        return null;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $payment = $this->getTargetPayment();

            if (! $payment) {
                return;
            }

            // Only pending payments can be rejected.
            // Verified or reversed payments cannot be rejected.
            if ($payment->status !== CollectionPayment::STATUS_PENDING) {
                $validator->errors()->add(
                    'payment',
                    __('portal.payments.already_processed')
                );
            }
        });
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        if ($this->wantsJson() || $this->ajax()) {
            $response = response()->json([
                'success' => false,
                'message' => collect($validator->errors()->all())->first() ?: 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);

            throw new ValidationException($validator, $response);
        }

        parent::failedValidation($validator);
    }
}
