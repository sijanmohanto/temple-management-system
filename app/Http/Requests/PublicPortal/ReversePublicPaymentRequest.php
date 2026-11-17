<?php

namespace App\Http\Requests\PublicPortal;

use App\Models\CollectionPayment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class ReversePublicPaymentRequest extends FormRequest
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
            || $user->hasPermission('reverse_collections')
            || $user->hasPermission('reverse_transactions')
            || $user->hasPermission('reverse_payments');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reversal_reason' => ['required', 'string', 'min:3', 'max:1000'],
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

            // Only verified payments can be reversed.
            if ($payment->status === CollectionPayment::STATUS_REVERSED) {
                $validator->errors()->add(
                    'payment',
                    __('portal.payments.already_processed')
                );

                return;
            }

            if ($payment->status !== CollectionPayment::STATUS_VERIFIED) {
                $validator->errors()->add(
                    'payment',
                    __('donations.only_verified_payments_can_be_reversed')
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
