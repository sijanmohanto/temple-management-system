<?php

namespace App\Http\Requests\Public;

use App\Models\CollectionPayment;
use App\Models\Event;
use App\Models\Pledge;
use App\Models\ReceiverAccount;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePublicPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && (bool) auth()->user()->person;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'string', 'in:bkash,nagad,rocket,bank_transfer'],
            'receiver_account_id' => ['required', 'integer', 'exists:receiver_accounts,id'],
            'amount' => ['required', 'numeric', 'min:1', 'max:10000000'],
            'payment_date' => ['required', 'date', 'before_or_equal:today'],
            'payment_time' => ['nullable', 'string', 'max:20'],
            'sender_mobile' => ['nullable', 'required_if:payment_method,bkash,nagad,rocket', 'string', 'max:20'],
            'transaction_reference' => ['nullable', 'required_if:payment_method,bkash,nagad,rocket', 'string', 'max:100'],
            'payment_proof' => ['nullable', 'file', 'mimes:jpeg,jpg,png,pdf,webp', 'max:5120'],
            'payment_notes' => ['nullable', 'string', 'max:500'],
            'pledge_id' => ['nullable', 'integer', 'exists:pledges,id'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $donor = auth()->user()?->person;
            if (! $donor) {
                $validator->errors()->add('donor', __('portal.public.account_not_linked_error'));

                return;
            }

            $eventSlug = $this->route('slug');
            $event = Event::where('slug', $eventSlug)->first();

            // Validate event collection date boundaries
            if ($event) {
                $today = now(config('app.timezone'))->startOfDay();

                if ($event->collection_start_date && $today->lt($event->collection_start_date->copy()->startOfDay())) {
                    $validator->errors()->add('event', __('portal.public.collection_not_started', [
                        'date' => $event->collection_start_date->format('d M Y'),
                    ]));
                }

                if ($event->collection_end_date && $today->gt($event->collection_end_date->copy()->endOfDay())) {
                    $validator->errors()->add('event', __('portal.public.collection_already_ended', [
                        'date' => $event->collection_end_date->format('d M Y'),
                    ]));
                }
            }

            // Verify receiver account is active, verified, matches payment method, and belongs to event or is general
            if ($this->filled('receiver_account_id')) {
                $receiverAccount = ReceiverAccount::find($this->input('receiver_account_id'));
                if (! $receiverAccount || ! $receiverAccount->is_active || ! $receiverAccount->is_verified) {
                    $validator->errors()->add('receiver_account_id', __('portal.public.no_receiver_account_found'));
                } elseif ($this->filled('payment_method') && $receiverAccount->payment_method !== $this->input('payment_method')) {
                    $validator->errors()->add('receiver_account_id', __('portal.public.no_receiver_account_found'));
                } elseif ($event && $receiverAccount->event_id !== null && (int) $receiverAccount->event_id !== (int) $event->id) {
                    $validator->errors()->add('receiver_account_id', __('portal.public.receiver_account_event_mismatch'));
                }
            }

            // Method-aware duplicate transaction reference prevention
            if ($this->filled('transaction_reference') && $this->filled('payment_method')) {
                $method = $this->input('payment_method');
                $trx = trim((string) $this->input('transaction_reference'));

                $exists = CollectionPayment::where('payment_method', $method)
                    ->where('transaction_reference', $trx)
                    ->whereIn('status', [CollectionPayment::STATUS_VERIFIED, CollectionPayment::STATUS_PENDING])
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('transaction_reference', __('portal.public.duplicate_transaction_error', [
                        'method' => strtoupper($method),
                    ]));
                }
            }

            // Validate pledge ownership if pledge_id provided
            if ($this->filled('pledge_id')) {
                $pledge = Pledge::find($this->input('pledge_id'));
                $eventSlug = $this->route('slug');
                $event = Event::where('slug', $eventSlug)->first();

                if (! $pledge || (int) $pledge->person_id !== (int) $donor->id || ($event && (int) $pledge->event_id !== (int) $event->id)) {
                    $validator->errors()->add('pledge_id', __('portal.public.invalid_pledge_selection'));
                }
            }
        });
    }

    /**
     * Custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'payment_method' => __('portal.public.payment_method'),
            'receiver_account_id' => __('portal.public.receiver_account_details'),
            'amount' => __('portal.public.amount'),
            'payment_date' => __('portal.public.payment_date'),
            'sender_mobile' => __('portal.public.sender_mobile'),
            'transaction_reference' => __('portal.public.transaction_reference'),
            'payment_proof' => __('portal.public.payment_proof'),
            'payment_notes' => __('portal.public.payment_notes'),
        ];
    }
}
