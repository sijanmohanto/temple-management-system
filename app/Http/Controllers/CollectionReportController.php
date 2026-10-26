<?php

namespace App\Http\Controllers;

use App\Models\CollectionPayment;
use App\Models\Event;
use App\Models\MonthlyCommitteeContribution;
use App\Models\PaymentAllocation;
use App\Models\Pledge;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class CollectionReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view_collection_reports'),
        ];
    }

    /**
     * Reports index / landing page.
     */
    public function index(): View
    {
        $events = Event::orderByDesc('start_date')->get();

        return view('donations.reports.index', compact('events'));
    }

    /**
     * Event-wise collection and pledge summary report.
     */
    public function eventSummary(Request $request): View
    {
        $selectedEventId = $request->input('event_id');
        $events = Event::orderByDesc('start_date')->get();

        $query = Event::with('category');
        if ($selectedEventId) {
            $query->where('id', $selectedEventId);
        }

        $reportEvents = $query->get()->map(function (Event $event) {
            $pledges = Pledge::where('event_id', $event->id)
                ->where('status', '!=', Pledge::STATUS_CANCELLED)
                ->get();

            $totalPledged = (float) $pledges->sum('pledged_amount');

            $verifiedPledgeCollections = (float) PaymentAllocation::where('event_id', $event->id)
                ->where('is_active', true)
                ->where('target_type', PaymentAllocation::TARGET_PLEDGE)
                ->whereHas('payment', fn ($q) => $q->where('status', CollectionPayment::STATUS_VERIFIED))
                ->sum('amount');

            $verifiedVoluntaryCollections = (float) PaymentAllocation::where('event_id', $event->id)
                ->where('is_active', true)
                ->where('target_type', PaymentAllocation::TARGET_VOLUNTARY_EVENT)
                ->whereHas('payment', fn ($q) => $q->where('status', CollectionPayment::STATUS_VERIFIED))
                ->sum('amount');

            $pendingCollections = (float) PaymentAllocation::where('event_id', $event->id)
                ->where('is_active', true)
                ->whereHas('payment', fn ($q) => $q->where('status', CollectionPayment::STATUS_PENDING))
                ->sum('amount');

            $outstandingPledges = max(0.0, round($totalPledged - $verifiedPledgeCollections, 2));
            $totalVerified = round($verifiedPledgeCollections + $verifiedVoluntaryCollections, 2);

            return [
                'event' => $event,
                'pledge_count' => $pledges->count(),
                'total_pledged' => $totalPledged,
                'verified_pledge_collections' => $verifiedPledgeCollections,
                'verified_voluntary_collections' => $verifiedVoluntaryCollections,
                'total_verified' => $totalVerified,
                'pending_collections' => $pendingCollections,
                'outstanding_pledges' => $outstandingPledges,
            ];
        });

        return view('donations.reports.event_summary', compact('reportEvents', 'events', 'selectedEventId'));
    }

    /**
     * Outstanding pledges report.
     */
    public function outstandingPledges(Request $request): View
    {
        $selectedEventId = $request->input('event_id');
        $events = Event::orderByDesc('start_date')->get();

        $query = Pledge::with(['event', 'person', 'family'])
            ->whereIn('status', [Pledge::STATUS_UNPAID, Pledge::STATUS_PARTIAL]);

        if ($selectedEventId) {
            $query->where('event_id', $selectedEventId);
        }

        $pledges = $query->latest('pledged_on')->get();

        $totalPledged = $pledges->sum('pledged_amount');
        $totalPaid = $pledges->sum(fn ($p) => $p->verified_paid_amount);
        $totalOutstanding = $pledges->sum(fn ($p) => $p->outstanding_amount);

        return view('donations.reports.outstanding_pledges', compact(
            'pledges',
            'events',
            'selectedEventId',
            'totalPledged',
            'totalPaid',
            'totalOutstanding'
        ));
    }

    /**
     * Committee monthly contributions summary report.
     */
    public function committeeContributions(Request $request): View
    {
        $year = (int) $request->input('year', date('Y'));

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthDate = Carbon::create($year, $m, 1)->toDateString();
            $contributions = MonthlyCommitteeContribution::whereDate('contribution_month', $monthDate)->get();

            $expected = (float) $contributions->where('status', '!=', MonthlyCommitteeContribution::STATUS_WAIVED)->sum('expected_amount');
            $paid = (float) $contributions->sum(fn ($c) => $c->verified_paid_amount);
            $outstanding = max(0.0, round($expected - $paid, 2));

            $months[] = [
                'month_name' => Carbon::create($year, $m, 1)->format('F Y'),
                'month_date' => $monthDate,
                'count' => $contributions->count(),
                'expected' => $expected,
                'paid' => $paid,
                'outstanding' => $outstanding,
            ];
        }

        return view('donations.reports.committee_contributions', compact('months', 'year'));
    }
}
