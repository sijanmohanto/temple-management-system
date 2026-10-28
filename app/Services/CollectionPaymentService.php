<?php

namespace App\Services;

use App\Models\CollectionPayment;
use App\Models\Event;
use App\Models\MonthlyCommitteeContribution;
use App\Models\PaymentAllocation;
use App\Models\Pledge;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CollectionPaymentService
{
    public function __construct(
        protected ReceiptService $receiptService,
        protected FinancePostingService $financePostingService
    ) {}

    /**
     * Submit a collection payment for verification.
     *
     * @throws ValidationException
     */
    public function submitPayment(array $paymentData, array $allocationsData, int $userId): CollectionPayment
    {
        $amount = round((float) ($paymentData['amount'] ?? 0.0), 2);
        if ($amount <= 0.0) {
            throw ValidationException::withMessages([
                'amount' => __('donations.amount_must_be_positive'),
            ]);
        }

        if (empty($allocationsData)) {
            throw ValidationException::withMessages([
                'allocations' => __('donations.at_least_one_allocation_required'),
            ]);
        }

        // Validate that sum of allocations strictly equals payment amount
        $allocationsSum = 0.0;
        foreach ($allocationsData as $alloc) {
            $allocAmount = round((float) ($alloc['amount'] ?? 0.0), 2);
            if ($allocAmount <= 0.0) {
                throw ValidationException::withMessages([
                    'allocations' => __('donations.allocation_amount_must_be_positive'),
                ]);
            }
            $allocationsSum += $allocAmount;
        }

        if (abs($amount - round($allocationsSum, 2)) > 0.009) {
            throw ValidationException::withMessages([
                'allocations' => __('donations.allocations_sum_must_equal_payment_amount', [
                    'payment' => number_format($amount, 2),
                    'allocated' => number_format($allocationsSum, 2),
                ]),
            ]);
        }

        $paymentDate = ! empty($paymentData['payment_date'])
            ? Carbon::parse($paymentData['payment_date'])
            : now();
        $year = (int) ($paymentData['payment_year'] ?? $paymentDate->year);

        return DB::transaction(function () use ($paymentData, $allocationsData, $amount, $paymentDate, $year, $userId) {
            $finalAllocations = [];

            // Lock and validate each allocation target, gracefully handling payments exceeding pledge/contribution
            foreach ($allocationsData as $alloc) {
                $targetType = $alloc['target_type'] ?? null;
                $allocAmount = round((float) $alloc['amount'], 2);

                if ($targetType === PaymentAllocation::TARGET_PLEDGE) {
                    $pledge = Pledge::where('id', $alloc['pledge_id'] ?? null)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($pledge->status === Pledge::STATUS_CANCELLED) {
                        throw ValidationException::withMessages([
                            'allocations' => __('donations.cannot_allocate_to_cancelled_pledge'),
                        ]);
                    }

                    $outstanding = (float) $pledge->outstanding_amount;
                    if ($allocAmount > ($outstanding + 0.009)) {
                        $pledgePortion = round($outstanding, 2);
                        $excessPortion = round($allocAmount - $pledgePortion, 2);

                        if ($pledgePortion > 0.0) {
                            $finalAllocations[] = [
                                'target_type' => PaymentAllocation::TARGET_PLEDGE,
                                'pledge_id' => $pledge->id,
                                'event_id' => $pledge->event_id,
                                'amount' => $pledgePortion,
                                'notes' => $alloc['notes'] ?? null,
                            ];
                        }

                        $excessTarget = ($alloc['excess_target_type'] ?? null) === PaymentAllocation::TARGET_VOLUNTARY_GENERAL
                            ? PaymentAllocation::TARGET_VOLUNTARY_GENERAL
                            : ($pledge->event_id ? PaymentAllocation::TARGET_VOLUNTARY_EVENT : PaymentAllocation::TARGET_VOLUNTARY_GENERAL);

                        $finalAllocations[] = [
                            'target_type' => $excessTarget,
                            'pledge_id' => $pledge->id,
                            'event_id' => $excessTarget === PaymentAllocation::TARGET_VOLUNTARY_EVENT ? $pledge->event_id : null,
                            'amount' => $excessPortion,
                            'notes' => __('donations.excess_pledge_voluntary_note', [
                                'code' => $pledge->pledge_code,
                                'amount' => number_format($excessPortion, 2),
                            ]),
                        ];
                    } else {
                        $finalAllocations[] = [
                            'target_type' => PaymentAllocation::TARGET_PLEDGE,
                            'pledge_id' => $pledge->id,
                            'event_id' => $pledge->event_id,
                            'amount' => $allocAmount,
                            'notes' => $alloc['notes'] ?? null,
                        ];
                    }
                } elseif ($targetType === PaymentAllocation::TARGET_MONTHLY_CONTRIBUTION) {
                    $contrib = MonthlyCommitteeContribution::where('id', $alloc['monthly_contribution_id'] ?? null)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($contrib->status === MonthlyCommitteeContribution::STATUS_WAIVED) {
                        throw ValidationException::withMessages([
                            'allocations' => __('donations.cannot_allocate_to_waived_contribution'),
                        ]);
                    }

                    $outstanding = (float) $contrib->outstanding_amount;
                    if ($allocAmount > ($outstanding + 0.009)) {
                        $contribPortion = round($outstanding, 2);
                        $excessPortion = round($allocAmount - $contribPortion, 2);

                        if ($contribPortion > 0.0) {
                            $finalAllocations[] = [
                                'target_type' => PaymentAllocation::TARGET_MONTHLY_CONTRIBUTION,
                                'monthly_contribution_id' => $contrib->id,
                                'amount' => $contribPortion,
                                'notes' => $alloc['notes'] ?? null,
                            ];
                        }

                        $finalAllocations[] = [
                            'target_type' => PaymentAllocation::TARGET_VOLUNTARY_GENERAL,
                            'monthly_contribution_id' => $contrib->id,
                            'amount' => $excessPortion,
                            'notes' => __('donations.excess_contribution_voluntary_note', [
                                'code' => $contrib->contribution_code,
                                'amount' => number_format($excessPortion, 2),
                            ]),
                        ];
                    } else {
                        $finalAllocations[] = [
                            'target_type' => PaymentAllocation::TARGET_MONTHLY_CONTRIBUTION,
                            'monthly_contribution_id' => $contrib->id,
                            'amount' => $allocAmount,
                            'notes' => $alloc['notes'] ?? null,
                        ];
                    }
                } elseif ($targetType === PaymentAllocation::TARGET_VOLUNTARY_EVENT) {
                    $event = Event::findOrFail($alloc['event_id'] ?? $paymentData['event_id'] ?? null);
                    if ($event->isLocked()) {
                        throw ValidationException::withMessages([
                            'allocations' => __('donations.cannot_donate_to_locked_event'),
                        ]);
                    }
                    $finalAllocations[] = [
                        'target_type' => PaymentAllocation::TARGET_VOLUNTARY_EVENT,
                        'event_id' => $event->id,
                        'amount' => $allocAmount,
                        'notes' => $alloc['notes'] ?? null,
                    ];
                } else {
                    $finalAllocations[] = [
                        'target_type' => PaymentAllocation::TARGET_VOLUNTARY_GENERAL,
                        'amount' => $allocAmount,
                        'notes' => $alloc['notes'] ?? null,
                    ];
                }
            }

            $code = CollectionPayment::generatePaymentCode($year);

            $collectionType = match ($paymentData['collection_type'] ?? '') {
                'pledge' => CollectionPayment::TYPE_EVENT_PLEDGE,
                'voluntary' => ! empty($paymentData['event_id']) ? CollectionPayment::TYPE_EVENT_DONATION : CollectionPayment::TYPE_GENERAL_DONATION,
                default => $paymentData['collection_type'] ?? CollectionPayment::TYPE_GENERAL_DONATION,
            };

            $payment = CollectionPayment::create([
                'payment_code' => $code,
                'payer_person_id' => $paymentData['payer_person_id'] ?? null,
                'payer_family_id' => $paymentData['payer_family_id'] ?? null,
                'payer_name_override' => $paymentData['payer_name_override'] ?? null,
                'payer_phone_override' => $paymentData['payer_phone_override'] ?? null,
                'collection_type' => $collectionType,
                'event_id' => $paymentData['event_id'] ?? null,
                'payment_date' => $paymentDate->toDateString(),
                'payment_year' => $year,
                'amount' => $amount,
                'payment_method' => $paymentData['payment_method'],
                'transaction_reference' => $paymentData['transaction_reference'] ?? null,
                'bank_name' => $paymentData['bank_name'] ?? null,
                'cheque_number' => $paymentData['cheque_number'] ?? null,
                'payment_notes' => $paymentData['payment_notes'] ?? null,
                'status' => CollectionPayment::STATUS_PENDING,
                'submitted_by' => $userId,
                'is_direct_cash_entry' => ! empty($paymentData['is_direct_cash_entry']),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            foreach ($finalAllocations as $alloc) {
                PaymentAllocation::create([
                    'collection_payment_id' => $payment->id,
                    'target_type' => $alloc['target_type'],
                    'pledge_id' => $alloc['pledge_id'] ?? null,
                    'monthly_contribution_id' => $alloc['monthly_contribution_id'] ?? null,
                    'event_id' => $alloc['event_id'] ?? null,
                    'amount' => round((float) $alloc['amount'], 2),
                    'notes' => $alloc['notes'] ?? null,
                    'is_active' => true,
                ]);
            }

            AuditLogger::log(
                'collection_payment_submitted',
                "Submitted payment {$payment->payment_code} for amount {$payment->amount} (Pending Verification)",
                $payment,
                $payment->toArray()
            );

            return $payment;
        });
    }

    /**
     * Verify an existing pending payment and issue official receipt.
     *
     * @throws ValidationException
     */
    public function verifyPayment(CollectionPayment $payment, int $verifierId, ?string $note = null): CollectionPayment
    {
        if ($payment->status !== CollectionPayment::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'payment' => __('donations.payment_not_in_pending_status'),
            ]);
        }

        // Separation of duties: submitter cannot verify own submission unless user is super admin
        if ($payment->submitted_by === $verifierId) {
            $user = User::find($verifierId);
            if (! $user || ! $user->hasRole('super_admin')) {
                throw ValidationException::withMessages([
                    'verification' => __('donations.cannot_verify_own_submission'),
                ]);
            }
        }

        return DB::transaction(function () use ($payment, $verifierId, $note) {
            /** @var CollectionPayment $lockedPayment */
            $lockedPayment = CollectionPayment::where('id', $payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPayment->status !== CollectionPayment::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'payment' => __('donations.payment_already_processed'),
                ]);
            }

            // Re-verify that allocations do not exceed outstanding balances upon concurrent verification
            foreach ($lockedPayment->allocations()->where('is_active', true)->get() as $allocation) {
                if ($allocation->target_type === PaymentAllocation::TARGET_PLEDGE && $allocation->pledge_id) {
                    $pledge = Pledge::where('id', $allocation->pledge_id)->lockForUpdate()->firstOrFail();
                    if ($pledge->status === Pledge::STATUS_CANCELLED) {
                        throw ValidationException::withMessages([
                            'verification' => __('donations.cannot_allocate_to_cancelled_pledge'),
                        ]);
                    }
                    if ($allocation->amount > ($pledge->outstanding_amount + 0.009)) {
                        $pledgePortion = round((float) $pledge->outstanding_amount, 2);
                        $excessPortion = round($allocation->amount - $pledgePortion, 2);

                        if ($pledgePortion > 0.0) {
                            $allocation->update(['amount' => $pledgePortion]);
                            PaymentAllocation::create([
                                'collection_payment_id' => $lockedPayment->id,
                                'target_type' => $pledge->event_id ? PaymentAllocation::TARGET_VOLUNTARY_EVENT : PaymentAllocation::TARGET_VOLUNTARY_GENERAL,
                                'pledge_id' => $pledge->id,
                                'event_id' => $pledge->event_id,
                                'amount' => $excessPortion,
                                'notes' => __('donations.excess_pledge_voluntary_note', [
                                    'code' => $pledge->pledge_code,
                                    'amount' => number_format($excessPortion, 2),
                                ]),
                                'is_active' => true,
                            ]);
                        } else {
                            $allocation->update([
                                'target_type' => $pledge->event_id ? PaymentAllocation::TARGET_VOLUNTARY_EVENT : PaymentAllocation::TARGET_VOLUNTARY_GENERAL,
                                'notes' => __('donations.excess_pledge_voluntary_note', [
                                    'code' => $pledge->pledge_code,
                                    'amount' => number_format($allocation->amount, 2),
                                ]),
                            ]);
                        }
                    }
                } elseif ($allocation->target_type === PaymentAllocation::TARGET_MONTHLY_CONTRIBUTION && $allocation->monthly_contribution_id) {
                    $contrib = MonthlyCommitteeContribution::where('id', $allocation->monthly_contribution_id)
                        ->lockForUpdate()
                        ->firstOrFail();
                    if ($contrib->status === MonthlyCommitteeContribution::STATUS_WAIVED) {
                        throw ValidationException::withMessages([
                            'verification' => __('donations.cannot_allocate_to_waived_contribution'),
                        ]);
                    }
                    if ($allocation->amount > ($contrib->outstanding_amount + 0.009)) {
                        $contribPortion = round((float) $contrib->outstanding_amount, 2);
                        $excessPortion = round($allocation->amount - $contribPortion, 2);

                        if ($contribPortion > 0.0) {
                            $allocation->update(['amount' => $contribPortion]);
                            PaymentAllocation::create([
                                'collection_payment_id' => $lockedPayment->id,
                                'target_type' => PaymentAllocation::TARGET_VOLUNTARY_GENERAL,
                                'monthly_contribution_id' => $contrib->id,
                                'amount' => $excessPortion,
                                'notes' => __('donations.excess_contribution_voluntary_note', [
                                    'code' => $contrib->contribution_code,
                                    'amount' => number_format($excessPortion, 2),
                                ]),
                                'is_active' => true,
                            ]);
                        } else {
                            $allocation->update([
                                'target_type' => PaymentAllocation::TARGET_VOLUNTARY_GENERAL,
                                'notes' => __('donations.excess_contribution_voluntary_note', [
                                    'code' => $contrib->contribution_code,
                                    'amount' => number_format($allocation->amount, 2),
                                ]),
                            ]);
                        }
                    }
                }
            }

            $lockedPayment->update([
                'status' => CollectionPayment::STATUS_VERIFIED,
                'verified_by' => $verifierId,
                'verified_at' => now(),
                'verification_note' => $note,
                'updated_by' => $verifierId,
            ]);

            // Atomically issue receipt
            $this->receiptService->issueReceipt($lockedPayment, $verifierId);

            // Recalculate status of affected pledges and contributions
            foreach ($lockedPayment->allocations()->where('is_active', true)->get() as $allocation) {
                if ($allocation->pledge) {
                    $allocation->pledge->recalculateStatus();
                }
                if ($allocation->monthlyContribution) {
                    $allocation->monthlyContribution->recalculateStatus();
                }
            }

            // Atomically post to finance ledger
            $this->financePostingService->postCollectionPayment($lockedPayment, $verifierId);

            AuditLogger::log(
                'collection_payment_verified',
                "Verified payment {$lockedPayment->payment_code} for amount {$lockedPayment->amount}",
                $lockedPayment,
                [
                    'verified_by' => $verifierId,
                    'note' => $note,
                ]
            );

            return $lockedPayment->fresh(['allocations', 'receipt']);
        });
    }

    /**
     * Reject a pending payment submission.
     *
     * @throws ValidationException
     */
    public function rejectPayment(CollectionPayment $payment, int $rejecterId, string $reason): CollectionPayment
    {
        if ($payment->status !== CollectionPayment::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'payment' => __('donations.payment_not_in_pending_status'),
            ]);
        }

        if (empty(trim($reason))) {
            throw ValidationException::withMessages([
                'rejection_reason' => __('donations.rejection_reason_required'),
            ]);
        }

        return DB::transaction(function () use ($payment, $rejecterId, $reason) {
            /** @var CollectionPayment $lockedPayment */
            $lockedPayment = CollectionPayment::where('id', $payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedPayment->update([
                'status' => CollectionPayment::STATUS_REJECTED,
                'rejected_by' => $rejecterId,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
                'updated_by' => $rejecterId,
            ]);

            // Deactivate allocations
            $lockedPayment->allocations()->update(['is_active' => false]);

            AuditLogger::log(
                'collection_payment_rejected',
                "Rejected payment {$lockedPayment->payment_code}. Reason: {$reason}",
                $lockedPayment,
                ['reason' => $reason]
            );

            return $lockedPayment->fresh(['allocations']);
        });
    }

    /**
     * Reverse a verified collection payment.
     *
     * @throws ValidationException
     */
    public function reversePayment(CollectionPayment $payment, int $reverserId, string $reason): CollectionPayment
    {
        if ($payment->status !== CollectionPayment::STATUS_VERIFIED) {
            throw ValidationException::withMessages([
                'payment' => __('donations.only_verified_payments_can_be_reversed'),
            ]);
        }

        if (empty(trim($reason))) {
            throw ValidationException::withMessages([
                'reversal_reason' => __('donations.reversal_reason_required'),
            ]);
        }

        return DB::transaction(function () use ($payment, $reverserId, $reason) {
            /** @var CollectionPayment $lockedPayment */
            $lockedPayment = CollectionPayment::where('id', $payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedPayment->update([
                'status' => CollectionPayment::STATUS_REVERSED,
                'reversed_by' => $reverserId,
                'reversed_at' => now(),
                'reversal_reason' => $reason,
                'updated_by' => $reverserId,
            ]);

            // Deactivate allocations
            $lockedPayment->allocations()->update(['is_active' => false]);

            // Void official receipt if issued
            if ($lockedPayment->receipt) {
                $this->receiptService->voidReceipt($lockedPayment->receipt, $reason, $reverserId);
            }

            // Recalculate status of affected pledges and contributions
            foreach ($lockedPayment->allocations as $allocation) {
                if ($allocation->pledge) {
                    $allocation->pledge->recalculateStatus();
                }
                if ($allocation->monthlyContribution) {
                    $allocation->monthlyContribution->recalculateStatus();
                }
            }

            // Reverse finance journal if posted
            if ($lockedPayment->isFinancePosted()) {
                $this->financePostingService->reverseCollectionPayment($lockedPayment, $reverserId, $reason);
            }

            AuditLogger::log(
                'collection_payment_reversed',
                "Reversed verified payment {$lockedPayment->payment_code}. Reason: {$reason}",
                $lockedPayment,
                ['reason' => $reason]
            );

            return $lockedPayment->fresh(['allocations', 'receipt']);
        });
    }
}
