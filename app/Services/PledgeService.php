<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Pledge;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PledgeService
{
    /**
     * Create a new event pledge.
     *
     * @throws ValidationException
     */
    public function createPledge(array $data, int $userId): Pledge
    {
        $hasPerson = ! empty($data['person_id']);
        $hasFamily = ! empty($data['family_id']);

        if (($hasPerson && $hasFamily) || (! $hasPerson && ! $hasFamily)) {
            throw ValidationException::withMessages([
                'pledge_owner' => __('donations.pledge_must_have_single_owner'),
            ]);
        }

        $event = Event::findOrFail($data['event_id']);
        if ($event->isLocked()) {
            throw ValidationException::withMessages([
                'event_id' => __('donations.cannot_pledge_to_locked_event'),
            ]);
        }

        $amount = (float) $data['pledged_amount'];
        if ($amount <= 0.0) {
            throw ValidationException::withMessages([
                'pledged_amount' => __('donations.pledged_amount_must_be_positive'),
            ]);
        }

        $year = (int) ($data['pledge_year'] ?? $event->event_year ?? date('Y'));

        return DB::transaction(function () use ($data, $year, $userId, $amount) {
            $code = Pledge::generatePledgeCode($year);

            $pledge = Pledge::create([
                'pledge_code' => $code,
                'event_id' => $data['event_id'],
                'person_id' => $data['person_id'] ?? null,
                'family_id' => $data['family_id'] ?? null,
                'pledge_year' => $year,
                'pledged_amount' => $amount,
                'pledged_on' => $data['pledged_on'] ?? now()->toDateString(),
                'purpose' => $data['purpose'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => Pledge::STATUS_UNPAID,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            AuditLogger::log(
                'pledge_created',
                "Created pledge {$pledge->pledge_code} for amount {$pledge->pledged_amount}",
                $pledge,
                $pledge->toArray()
            );

            return $pledge;
        });
    }

    /**
     * Update an existing pledge.
     *
     * @throws ValidationException
     */
    public function updatePledge(Pledge $pledge, array $data, int $userId): Pledge
    {
        if ($pledge->status === Pledge::STATUS_CANCELLED) {
            throw ValidationException::withMessages([
                'pledge' => __('donations.cannot_update_cancelled_pledge'),
            ]);
        }

        $newAmount = isset($data['pledged_amount']) ? (float) $data['pledged_amount'] : (float) $pledge->pledged_amount;
        if ($newAmount <= 0.0) {
            throw ValidationException::withMessages([
                'pledged_amount' => __('donations.pledged_amount_must_be_positive'),
            ]);
        }

        // Prevent reducing pledged amount below already verified collected payments
        $verifiedPaid = $pledge->verified_paid_amount;
        if ($newAmount < $verifiedPaid) {
            throw ValidationException::withMessages([
                'pledged_amount' => __('donations.pledged_amount_less_than_collected', [
                    'amount' => number_format($verifiedPaid, 2),
                ]),
            ]);
        }

        return DB::transaction(function () use ($pledge, $data, $newAmount, $userId) {
            $old = $pledge->toArray();

            $updateData = [
                'pledged_amount' => $newAmount,
                'purpose' => $data['purpose'] ?? $pledge->purpose,
                'notes' => $data['notes'] ?? $pledge->notes,
                'updated_by' => $userId,
            ];

            if (! empty($data['pledged_on'])) {
                $updateData['pledged_on'] = $data['pledged_on'];
            }

            $pledge->update($updateData);
            $pledge->recalculateStatus();

            AuditLogger::log(
                'pledge_updated',
                "Updated pledge {$pledge->pledge_code}",
                $pledge,
                [
                    'old' => $old,
                    'new' => $pledge->fresh()->toArray(),
                ]
            );

            return $pledge->fresh();
        });
    }

    /**
     * Cancel a pledge.
     *
     * @throws ValidationException
     */
    public function cancelPledge(Pledge $pledge, string $reason, int $userId): bool
    {
        if ($pledge->status === Pledge::STATUS_CANCELLED) {
            return true;
        }

        if ($pledge->verified_paid_amount > 0.0) {
            throw ValidationException::withMessages([
                'pledge' => __('donations.cannot_cancel_pledge_with_payments'),
            ]);
        }

        return DB::transaction(function () use ($pledge, $reason, $userId) {
            $pledge->update([
                'status' => Pledge::STATUS_CANCELLED,
                'cancelled_by' => $userId,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
                'updated_by' => $userId,
            ]);

            AuditLogger::log(
                'pledge_cancelled',
                "Cancelled pledge {$pledge->pledge_code}. Reason: {$reason}",
                $pledge,
                ['reason' => $reason]
            );

            return true;
        });
    }
}
