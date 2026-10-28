<?php

namespace App\Services;

use App\Models\CollectionPayment;
use App\Models\Event;
use App\Models\Expense;
use App\Models\PaymentAllocation;
use App\Models\Pledge;

class EventCollectionReportService
{
    /**
     * Compute comprehensive financial and collection metrics for an event.
     */
    public function getMetrics(Event $event): array
    {
        $pledges = Pledge::where('event_id', $event->id)
            ->where('status', '!=', Pledge::STATUS_CANCELLED)
            ->get();

        $totalPledged = round((float) $pledges->sum('pledged_amount'), 2);

        // Verified allocations applied to pledges of this event
        $verifiedPledgeCollections = round((float) PaymentAllocation::where('event_id', $event->id)
            ->where('is_active', true)
            ->where('target_type', PaymentAllocation::TARGET_PLEDGE)
            ->whereHas('payment', fn ($q) => $q->where('status', CollectionPayment::STATUS_VERIFIED))
            ->sum('amount'), 2);

        // Excess contributions originating from pledges or directly attributed to this event
        $eventExcessContributions = round((float) PaymentAllocation::where('event_id', $event->id)
            ->where('is_active', true)
            ->where('target_type', PaymentAllocation::TARGET_VOLUNTARY_EVENT)
            ->whereNotNull('pledge_id')
            ->whereHas('payment', fn ($q) => $q->where('status', CollectionPayment::STATUS_VERIFIED))
            ->sum('amount'), 2);

        // Direct voluntary donations to this event (e.g. guests/devotees without a pledge)
        $voluntaryEventDonations = round((float) PaymentAllocation::where('event_id', $event->id)
            ->where('is_active', true)
            ->where('target_type', PaymentAllocation::TARGET_VOLUNTARY_EVENT)
            ->whereNull('pledge_id')
            ->whereHas('payment', fn ($q) => $q->where('status', CollectionPayment::STATUS_VERIFIED))
            ->sum('amount'), 2);

        // Total verified collections received for this event
        $totalVerifiedCollections = round(
            $verifiedPledgeCollections + $eventExcessContributions + $voluntaryEventDonations,
            2
        );

        // Pending unverified collections
        $pendingCollections = round((float) PaymentAllocation::where('event_id', $event->id)
            ->where('is_active', true)
            ->whereHas('payment', fn ($q) => $q->where('status', CollectionPayment::STATUS_PENDING))
            ->sum('amount'), 2);

        // Outstanding pledges (target shortfall on pledged amounts)
        $outstandingPledges = max(0.00, round($totalPledged - $verifiedPledgeCollections, 2));

        // Target comparisons
        $isAboveTarget = $totalPledged > 0 && ($totalVerifiedCollections > $totalPledged);
        $aboveTargetAmount = $isAboveTarget ? round($totalVerifiedCollections - $totalPledged, 2) : 0.00;
        $shortfallAmount = ($totalPledged > 0 && $totalVerifiedCollections < $totalPledged)
            ? round($totalPledged - $totalVerifiedCollections, 2)
            : 0.00;

        // Approved expenses for this event
        $totalApprovedExpenses = round((float) Expense::where('event_id', $event->id)
            ->where('status', Expense::STATUS_APPROVED)
            ->sum('amount'), 2);

        // Net event cash surplus / deficit
        $netSurplus = round($totalVerifiedCollections - $totalApprovedExpenses, 2);

        // Settlement status
        $settlement = $event->settlement;
        $isSettled = $settlement && $settlement->isCompleted();

        return [
            'total_pledged' => $totalPledged,
            'verified_pledge_collections' => $verifiedPledgeCollections,
            'event_excess_contributions' => $eventExcessContributions,
            'voluntary_event_donations' => $voluntaryEventDonations,
            'total_verified_collections' => $totalVerifiedCollections,
            'pending_collections' => $pendingCollections,
            'outstanding_pledges' => $outstandingPledges,
            'is_above_target' => $isAboveTarget,
            'above_target_amount' => $aboveTargetAmount,
            'shortfall_amount' => $shortfallAmount,
            'total_approved_expenses' => $totalApprovedExpenses,
            'net_surplus' => $netSurplus,
            'is_settled' => $isSettled,
            'settlement' => $settlement,
        ];
    }

    /**
     * Get detailed list of donors whose contributions exceeded their pledge amount.
     */
    public function getAboveTargetDonors(Event $event): array
    {
        $pledges = Pledge::with(['person', 'family', 'allocations.payment.receipt'])
            ->where('event_id', $event->id)
            ->where('status', '!=', Pledge::STATUS_CANCELLED)
            ->get();

        $aboveTarget = [];

        foreach ($pledges as $pledge) {
            $verifiedPaid = (float) $pledge->verified_paid_amount;
            $excessAmount = (float) $pledge->excess_contributed_amount;
            $totalContributed = round($verifiedPaid + $excessAmount, 2);
            $pledged = (float) $pledge->pledged_amount;

            if ($excessAmount > 0.009 || $totalContributed > ($pledged + 0.009)) {
                // Determine destination from allocations
                $excessAllocations = $pledge->allocations
                    ->where('is_active', true)
                    ->whereIn('target_type', [
                        PaymentAllocation::TARGET_VOLUNTARY_EVENT,
                        PaymentAllocation::TARGET_VOLUNTARY_GENERAL,
                    ]);

                $destinations = [];
                $receipts = [];

                foreach ($excessAllocations as $alloc) {
                    $dest = $alloc->target_type === PaymentAllocation::TARGET_VOLUNTARY_EVENT
                        ? __('donations.excess_destination_event')
                        : __('donations.excess_destination_general');
                    if (! in_array($dest, $destinations, true)) {
                        $destinations[] = $dest;
                    }
                    if ($alloc->payment?->receipt) {
                        $receipts[] = $alloc->payment->receipt->receipt_number;
                    }
                }

                $aboveTarget[] = [
                    'pledge_id' => $pledge->id,
                    'pledge_code' => $pledge->pledge_code,
                    'donor_name' => $pledge->owner_name,
                    'donor_type' => $pledge->person_id ? 'Person' : 'Family',
                    'donor_phone' => $pledge->person?->phone ?? $pledge->family?->primaryContact?->phone ?? '—',
                    'pledged_amount' => $pledged,
                    'verified_pledge_amount' => $verifiedPaid,
                    'excess_amount' => $excessAmount > 0 ? $excessAmount : max(0.00, round($totalContributed - $pledged, 2)),
                    'total_contributed' => $totalContributed,
                    'destinations' => ! empty($destinations) ? implode(', ', $destinations) : __('donations.excess_destination_event'),
                    'receipt_numbers' => ! empty($receipts) ? implode(', ', array_unique($receipts)) : '—',
                    'status' => $pledge->status,
                ];
            }
        }

        return $aboveTarget;
    }

    /**
     * Get detailed list of pledges with outstanding dues for this event.
     */
    public function getOutstandingDues(Event $event): array
    {
        $pledges = Pledge::with(['person', 'family', 'allocations.payment'])
            ->where('event_id', $event->id)
            ->whereIn('status', [Pledge::STATUS_UNPAID, Pledge::STATUS_PARTIAL])
            ->get();

        $dues = [];

        foreach ($pledges as $pledge) {
            $outstanding = (float) $pledge->outstanding_amount;
            if ($outstanding <= 0.009) {
                continue;
            }

            // Find last verified payment date
            $lastPayment = $pledge->allocations
                ->where('is_active', true)
                ->where('target_type', PaymentAllocation::TARGET_PLEDGE)
                ->filter(fn ($a) => $a->payment && $a->payment->isVerified())
                ->sortByDesc(fn ($a) => $a->payment->payment_date)
                ->first();

            $dues[] = [
                'pledge_id' => $pledge->id,
                'pledge_code' => $pledge->pledge_code,
                'donor_name' => $pledge->owner_name,
                'donor_phone' => $pledge->person?->phone ?? $pledge->family?->primaryContact?->phone ?? '—',
                'donor_type' => $pledge->person_id ? 'Person' : 'Family',
                'pledged_amount' => (float) $pledge->pledged_amount,
                'verified_paid' => (float) $pledge->verified_paid_amount,
                'outstanding_amount' => $outstanding,
                'last_payment_date' => $lastPayment ? $lastPayment->payment->payment_date->format('d M Y') : '—',
                'status' => $pledge->status,
            ];
        }

        return $dues;
    }

    /**
     * Get breakdown of excess contributions originating from this event.
     */
    public function getExcessContributionReport(Event $event): array
    {
        $allocations = PaymentAllocation::with(['payment.receipt', 'pledge.person', 'pledge.family'])
            ->where('is_active', true)
            ->whereIn('target_type', [
                PaymentAllocation::TARGET_VOLUNTARY_EVENT,
                PaymentAllocation::TARGET_VOLUNTARY_GENERAL,
            ])
            ->whereHas('pledge', fn ($q) => $q->where('event_id', $event->id))
            ->whereHas('payment', fn ($q) => $q->where('status', CollectionPayment::STATUS_VERIFIED))
            ->latest('id')
            ->get();

        $rows = [];

        foreach ($allocations as $alloc) {
            $destination = $alloc->target_type === PaymentAllocation::TARGET_VOLUNTARY_EVENT
                ? __('donations.excess_destination_event')
                : __('donations.excess_destination_general');

            $rows[] = [
                'donor_name' => $alloc->pledge?->owner_name ?? $alloc->payment?->payer_display_name ?? '—',
                'pledge_code' => $alloc->pledge?->pledge_code ?? '—',
                'amount' => (float) $alloc->amount,
                'destination_fund' => $destination,
                'target_type' => $alloc->target_type,
                'payment_date' => $alloc->payment?->payment_date ? $alloc->payment->payment_date->format('d M Y') : '—',
                'payment_code' => $alloc->payment?->payment_code ?? '—',
                'receipt_number' => $alloc->payment?->receipt?->receipt_number ?? '—',
                'receipt_id' => $alloc->payment?->receipt?->id,
            ];
        }

        return $rows;
    }

    /**
     * Individual donor comparison for all contributors to this event.
     */
    public function getDonorComparison(Event $event): array
    {
        $rows = [];

        // 1. Pledged Donors
        $pledges = Pledge::with(['person', 'family', 'allocations.payment'])
            ->where('event_id', $event->id)
            ->where('status', '!=', Pledge::STATUS_CANCELLED)
            ->get();

        foreach ($pledges as $pledge) {
            $verifiedPaid = (float) $pledge->verified_paid_amount;
            $excess = (float) $pledge->excess_contributed_amount;
            $outstanding = (float) $pledge->outstanding_amount;

            $rows[] = [
                'donor_name' => $pledge->owner_name,
                'donor_type' => $pledge->person_id ? 'Registered Devotee' : 'Family Unit',
                'pledge_code' => $pledge->pledge_code,
                'pledge_id' => $pledge->id,
                'pledged_amount' => (float) $pledge->pledged_amount,
                'verified_applied' => $verifiedPaid,
                'excess_amount' => $excess,
                'outstanding_amount' => $outstanding,
                'status' => $pledge->status,
                'is_pledge' => true,
            ];
        }

        // 2. Direct Voluntary / Guest Donors (without pledge)
        $directAllocations = PaymentAllocation::with(['payment.payerPerson', 'payment.payerFamily', 'payment.receipt'])
            ->where('event_id', $event->id)
            ->where('is_active', true)
            ->where('target_type', PaymentAllocation::TARGET_VOLUNTARY_EVENT)
            ->whereNull('pledge_id')
            ->whereHas('payment', fn ($q) => $q->where('status', CollectionPayment::STATUS_VERIFIED))
            ->get();

        foreach ($directAllocations as $alloc) {
            $payment = $alloc->payment;
            $donorType = 'Guest / Visitor';
            if ($payment?->payerPerson) {
                $donorType = 'Registered Devotee';
            } elseif ($payment?->payerFamily) {
                $donorType = 'Family Unit';
            }

            $rows[] = [
                'donor_name' => $payment?->payer_display_name ?? 'Walk-in Guest',
                'donor_type' => $donorType,
                'pledge_code' => '— (Voluntary)',
                'pledge_id' => null,
                'pledged_amount' => 0.00,
                'verified_applied' => 0.00,
                'excess_amount' => 0.00,
                'voluntary_amount' => (float) $alloc->amount,
                'outstanding_amount' => 0.00,
                'status' => 'voluntary',
                'is_pledge' => false,
                'payment_id' => $payment?->id,
                'receipt_number' => $payment?->receipt?->receipt_number,
            ];
        }

        return $rows;
    }
}
