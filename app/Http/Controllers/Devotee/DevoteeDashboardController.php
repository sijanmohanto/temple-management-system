<?php

namespace App\Http\Controllers\Devotee;

use App\Http\Controllers\Controller;
use App\Models\CollectionPayment;
use App\Models\Event;
use App\Models\PaymentAllocation;
use App\Models\Pledge;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DevoteeDashboardController extends Controller
{
    /**
     * Display the Devotee Self-Service Dashboard.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $person = $user->person;

        // 1. Calculate Authoritative Financial Metrics
        $pledges = Pledge::with(['allocations.payment', 'event'])
            ->where('person_id', $person->id)
            ->where('status', '!=', Pledge::STATUS_CANCELLED)
            ->get();

        $totalPledged = (float) $pledges->sum('pledged_amount');

        // Verified payments strictly count towards Paid
        $verifiedPledgePaid = (float) $pledges->sum(fn ($p) => $p->verified_paid_amount);

        $verifiedVoluntaryPaid = (float) PaymentAllocation::where('is_active', true)
            ->whereIn('target_type', [
                PaymentAllocation::TARGET_VOLUNTARY_EVENT,
                PaymentAllocation::TARGET_VOLUNTARY_GENERAL,
            ])
            ->whereHas('payment', function ($q) use ($person) {
                $q->where('payer_person_id', $person->id)
                    ->where('status', CollectionPayment::STATUS_VERIFIED);
            })
            ->sum('amount');

        $totalPaid = round($verifiedPledgePaid + $verifiedVoluntaryPaid, 2);

        // Voluntary donations do not reduce pledge due; only verified pledge allocations do
        $totalDue = (float) $pledges->sum(fn ($p) => $p->outstanding_amount);

        // Pending payments awaiting verification
        $pendingPayments = (float) CollectionPayment::where('payer_person_id', $person->id)
            ->where('status', CollectionPayment::STATUS_PENDING)
            ->sum('amount');

        // 2. Eligible Running Events (Each handled independently)
        $runningEvents = Event::runningForPayment()
            ->with('category')
            ->orderBy('start_date', 'desc')
            ->get();

        $runningEventCards = $runningEvents->map(function (Event $event) use ($person) {
            $pledge = Pledge::where('person_id', $person->id)
                ->where('event_id', $event->id)
                ->where('status', '!=', Pledge::STATUS_CANCELLED)
                ->first();

            $pledged = $pledge ? (float) $pledge->pledged_amount : 0.0;
            $paid = $pledge ? (float) $pledge->verified_paid_amount : 0.0;
            $due = $pledge ? (float) $pledge->outstanding_amount : 0.0;

            $status = 'no_pledge';
            if ($pledge) {
                if ($due <= 0.009 && $pledged > 0) {
                    $status = 'paid';
                } elseif ($paid > 0) {
                    $status = 'partial';
                } else {
                    $status = 'unpaid';
                }
            }

            return [
                'event' => $event,
                'pledge' => $pledge,
                'pledged' => $pledged,
                'paid' => $paid,
                'due' => $due,
                'status' => $status,
                'pay_url' => route('public.events.pay', $event->slug).($pledge ? '?pledge_id='.$pledge->id : ''),
            ];
        });

        // 3. Recent Payment Submissions
        $recentPayments = CollectionPayment::with([
            'event',
            'receiverAccount',
            'receipt',
        ])
            ->where('payer_person_id', $person->id)
            ->latest('id')
            ->take(8)
            ->get();

        return view('devotee.dashboard', compact(
            'user',
            'person',
            'totalPledged',
            'totalPaid',
            'totalDue',
            'pendingPayments',
            'runningEventCards',
            'recentPayments'
        ));
    }
}
