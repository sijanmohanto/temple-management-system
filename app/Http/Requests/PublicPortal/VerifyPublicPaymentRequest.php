<?php

namespace App\Http\Requests\PublicPortal;

use App\Models\CollectionPayment;
use App\Models\Person;
use App\Models\Pledge;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class VerifyPublicPaymentRequest extends FormRequest
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

        return $user->hasRole('super_admin') || $user->hasPermission('verify_public_payments');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'verification_note' => ['nullable', 'string', 'max:1000'],
            'person_id' => ['nullable', 'integer', 'exists:people,id'],
            'pledge_id' => ['nullable', 'integer', 'exists:pledges,id'],
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

            // 1. Target payment must be eligible for verification (pending status)
            if ($payment->status !== CollectionPayment::STATUS_PENDING) {
                $validator->errors()->add(
                    'payment',
                    __('portal.payments.already_processed')
                );

                return;
            }

            // 2. Separation of duties: Administrative submitter cannot approve own submission
            // unless the user is a super admin
            $user = $this->user();
            if ($payment->submitted_by && $user && (int) $payment->submitted_by === (int) $user->id) {
                if (! $user->hasRole('super_admin')) {
                    $validator->errors()->add(
                        'verification',
                        __('donations.cannot_verify_own_submission')
                    );
                }
            }

            // 3. Devotee / Person verification & active status
            $effectivePersonId = null;
            if ($this->filled('person_id')) {
                $targetPerson = Person::find($this->integer('person_id'));
                if (! $targetPerson || $targetPerson->status !== 'active') {
                    $validator->errors()->add(
                        'person_id',
                        __('portal.payments.inactive_or_invalid_person')
                    );
                } else {
                    $effectivePersonId = $targetPerson->id;
                }
            } else {
                $effectivePersonId = $payment->payer_person_id;
                if ($effectivePersonId) {
                    $payerPerson = $payment->payerPerson ?: Person::find($effectivePersonId);
                    if (! $payerPerson || $payerPerson->status !== 'active') {
                        $validator->errors()->add(
                            'person_id',
                            __('portal.payments.inactive_or_invalid_person')
                        );
                    }
                }
            }

            // Public payments must be submitted/linked against a valid devotee
            if (! $effectivePersonId && ! $this->filled('person_id')) {
                $validator->errors()->add(
                    'person_id',
                    __('portal.payments.payment_against_pledge_only')
                );
            }

            // 4. Method-aware duplicate transaction reference prevention
            if ($payment->transaction_reference && in_array($payment->payment_method, [
                CollectionPayment::METHOD_BKASH,
                CollectionPayment::METHOD_NAGAD,
                CollectionPayment::METHOD_ROCKET,
                CollectionPayment::METHOD_BANK_TRANSFER,
            ])) {
                $existingTrx = CollectionPayment::where('id', '!=', $payment->id)
                    ->where('payment_method', $payment->payment_method)
                    ->where('transaction_reference', $payment->transaction_reference)
                    ->whereIn('status', [CollectionPayment::STATUS_VERIFIED, CollectionPayment::STATUS_PENDING])
                    ->exists();

                if ($existingTrx) {
                    $validator->errors()->add(
                        'transaction_reference',
                        __('portal.payments.duplicate_transaction_reference', [
                            'method' => strtoupper($payment->payment_method),
                            'trx' => $payment->transaction_reference,
                        ])
                    );
                }
            }

            // 5. Pledge ownership and event validation
            $pledgeId = $this->filled('pledge_id') ? $this->integer('pledge_id') : $payment->pledge_id;
            if ($pledgeId) {
                $pledge = Pledge::find($pledgeId);
                if ($pledge) {
                    if ($pledge->status === Pledge::STATUS_CANCELLED) {
                        $validator->errors()->add(
                            'pledge_id',
                            __('donations.cannot_allocate_to_cancelled_pledge')
                        );
                    }

                    // Prevent pledge belonging to another Person
                    if ($effectivePersonId && (int) $pledge->person_id !== (int) $effectivePersonId) {
                        $validator->errors()->add(
                            'pledge_id',
                            __('portal.payments.pledge_donor_mismatch')
                        );
                    }

                    // Prevent pledge belonging to another Event
                    if ($payment->event_id && (int) $pledge->event_id !== (int) $payment->event_id) {
                        $validator->errors()->add(
                            'pledge_id',
                            __('portal.payments.pledge_event_mismatch')
                        );
                    }
                }
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
