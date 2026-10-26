<?php

namespace App\Http\Controllers;

use App\Models\CollectionPayment;
use App\Models\Event;
use App\Models\MonthlyCommitteeContribution;
use App\Models\PaymentAllocation;
use App\Models\Pledge;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class CollectionDashboardController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view_collections'),
        ];
    }

    /**
     * Display the collection & donation management dashboard.
     */
    public function index(): View
    {
        $currentYear = (int) date('Y');

        $verifiedQuery = CollectionPayment::where('status', CollectionPayment::STATUS_VERIFIED);
        $pendingQuery = CollectionPayment::where('status', CollectionPayment::STATUS_PENDING);

        $metrics = [
            'total_verified_amount' => (float) (clone $verifiedQuery)->sum('amount'),
            'total_verified_count' => (clone $verifiedQuery)->count(),
            'pending_amount' => (float) (clone $pendingQuery)->sum('amount'),
            'pending_count' => (clone $pendingQuery)->count(),

            'event_pledge_collections' => (float) PaymentAllocation::where('is_active', true)
                ->where('target_type', PaymentAllocation::TARGET_PLEDGE)
                ->whereHas('payment', fn ($q) => $q->where('status', CollectionPayment::STATUS_VERIFIED))
                ->sum('amount'),

            'event_voluntary_collections' => (float) PaymentAllocation::where('is_active', true)
                ->where('target_type', PaymentAllocation::TARGET_VOLUNTARY_EVENT)
                ->whereHas('payment', fn ($q) => $q->where('status', CollectionPayment::STATUS_VERIFIED))
                ->sum('amount'),

            'monthly_committee_collections' => (float) PaymentAllocation::where('is_active', true)
                ->where('target_type', PaymentAllocation::TARGET_MONTHLY_CONTRIBUTION)
                ->whereHas('payment', fn ($q) => $q->where('status', CollectionPayment::STATUS_VERIFIED))
                ->sum('amount'),

            'general_donation_collections' => (float) PaymentAllocation::where('is_active', true)
                ->where('target_type', PaymentAllocation::TARGET_VOLUNTARY_GENERAL)
                ->whereHas('payment', fn ($q) => $q->where('status', CollectionPayment::STATUS_VERIFIED))
                ->sum('amount'),

            'pledge_count' => Pledge::where('status', '!=', Pledge::STATUS_CANCELLED)->count(),
            'pledge_total_amount' => (float) Pledge::where('status', '!=', Pledge::STATUS_CANCELLED)->sum('pledged_amount'),

            'committee_contributions_expected' => (float) MonthlyCommitteeContribution::where('status', '!=', MonthlyCommitteeContribution::STATUS_WAIVED)->sum('expected_amount'),
        ];

        $metrics['pledge_outstanding'] = max(0.0, round($metrics['pledge_total_amount'] - $metrics['event_pledge_collections'], 2));
        $metrics['committee_contributions_outstanding'] = max(0.0, round($metrics['committee_contributions_expected'] - $metrics['monthly_committee_collections'], 2));

        $recentVerified = CollectionPayment::with(['payerPerson', 'payerFamily', 'event', 'receipt'])
            ->where('status', CollectionPayment::STATUS_VERIFIED)
            ->latest('verified_at')
            ->take(6)
            ->get();

        $pendingQueue = CollectionPayment::with(['payerPerson', 'payerFamily', 'event', 'submitter'])
            ->where('status', CollectionPayment::STATUS_PENDING)
            ->oldest('created_at')
            ->take(6)
            ->get();

        $activeEvents = Event::whereIn('status', [Event::STATUS_ACTIVE, Event::STATUS_UPCOMING, Event::STATUS_CLOSING])
            ->withCount('pledges')
            ->orderBy('start_date')
            ->take(5)
            ->get();

        return view('donations.dashboard', compact('metrics', 'recentVerified', 'pendingQueue', 'activeEvents', 'currentYear'));
    }
}
