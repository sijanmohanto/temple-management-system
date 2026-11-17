<?php

namespace App\Http\Requests\Public;

use App\Models\CollectionPayment;
use App\Models\Complaint;
use App\Models\Event;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreDonorComplaintRequest extends FormRequest
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
            'complaint_type' => ['required', 'string', 'in:payment_not_updated,wrong_paid_amount,wrong_due_amount,other'],
            'description' => ['required', 'string', 'min:5', 'max:2000'],
            'payment_code' => ['nullable', 'string', 'max:50', 'exists:collection_payments,payment_code'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'attachment' => ['nullable', 'file', 'mimes:jpeg,jpg,png,pdf', 'max:5120'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            $donor = $user?->person;

            if (! $donor) {
                $validator->errors()->add('donor', __('portal.public.account_not_linked_error'));

                return;
            }

            $payment = null;
            // Server-side ownership validation for related payment
            if ($this->filled('payment_code')) {
                $payment = CollectionPayment::where('payment_code', trim($this->input('payment_code')))->first();
                if ($payment) {
                    $isOwner = ((int) $payment->payer_person_id === (int) $donor->id)
                        || ((int) $payment->submitted_by === (int) $user->id);

                    if (! $isOwner) {
                        $validator->errors()->add(
                            'payment_code',
                            __('portal.public.invalid_payment_ownership')
                        );
                    }
                }
            }

            // Consistency between payment and event
            if ($payment && $this->filled('event_id')) {
                $selectedEventId = (int) $this->input('event_id');
                if ($payment->event_id && (int) $payment->event_id !== $selectedEventId) {
                    $validator->errors()->add(
                        'event_id',
                        __('portal.public.event_payment_mismatch')
                    );
                } elseif (! $payment->event_id) {
                    $validator->errors()->add(
                        'event_id',
                        __('portal.public.event_payment_mismatch')
                    );
                }
            }

            // Verify event is published and not draft/inactive
            if ($this->filled('event_id')) {
                $event = Event::find($this->input('event_id'));
                $isPublished = $event && ($event->publication_status === Event::PUB_STATUS_PUBLISHED || (bool) $event->is_published);
                if (! $event || $event->status === Event::STATUS_DRAFT || ! $isPublished) {
                    $validator->errors()->add(
                        'event_id',
                        __('portal.public.invalid_or_unpublished_event')
                    );
                }
            }

            // Anti-duplicate submission check: Scoped to the same donor within 10 minutes
            $recentDuplicate = Complaint::where('person_id', $donor->id)
                ->where('category', $this->input('complaint_type'))
                ->where('description', trim($this->input('description')))
                ->where('created_at', '>=', now()->subMinutes(10))
                ->exists();

            if ($recentDuplicate) {
                $validator->errors()->add(
                    'description',
                    __('portal.public.anti_duplicate_error')
                );
            }
        });
    }
}
