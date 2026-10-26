<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkGenerateMonthlyContributionRequest;
use App\Http\Requests\StoreMonthlyContributionRequest;
use App\Models\Committee;
use App\Models\CommitteeMembership;
use App\Models\MonthlyCommitteeContribution;
use App\Services\MonthlyContributionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MonthlyContributionController extends Controller implements HasMiddleware
{
    public function __construct(
        protected MonthlyContributionService $contributionService
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:view_committee_contributions', only: ['index']),
            new Middleware('permission:manage_committee_contributions', except: ['index']),
        ];
    }

    /**
     * Display listing of monthly committee contributions.
     */
    public function index(Request $request): View|JsonResponse
    {
        $selectedMonth = $request->input('month', now()->format('Y-m'));
        $monthDate = Carbon::parse($selectedMonth)->startOfMonth()->toDateString();
        $selectedCommitteeId = $request->input('committee_id');

        $query = MonthlyCommitteeContribution::query()
            ->with(['membership.person', 'membership.committee', 'allocations.payment'])
            ->whereDate('contribution_month', $monthDate);

        if ($selectedCommitteeId) {
            $query->whereHas('membership', fn ($q) => $q->where('committee_id', $selectedCommitteeId));
        }

        $contributions = $query->get();

        $committees = Committee::where('status', 'active')->orderBy('name_en')->get();
        if (! $selectedCommitteeId && $committees->isNotEmpty()) {
            $selectedCommitteeId = $committees->first()->id;
        }

        $activeMemberships = CommitteeMembership::with('person')
            ->where('committee_id', $selectedCommitteeId)
            ->where('status', 'active')
            ->get();

        $totalExpected = $contributions->where('status', '!=', MonthlyCommitteeContribution::STATUS_WAIVED)->sum('expected_amount');
        $totalPaid = $contributions->sum(fn ($c) => $c->verified_paid_amount);
        $totalOutstanding = max(0.0, round($totalExpected - $totalPaid, 2));

        return view('donations.contributions.index', compact(
            'contributions',
            'committees',
            'selectedMonth',
            'selectedCommitteeId',
            'activeMemberships',
            'totalExpected',
            'totalPaid',
            'totalOutstanding'
        ));
    }

    /**
     * Store individual monthly contribution obligation.
     */
    public function store(StoreMonthlyContributionRequest $request): RedirectResponse
    {
        $this->contributionService->createContribution(
            (int) $request->input('committee_membership_id'),
            $request->input('contribution_month'),
            (float) $request->input('expected_amount'),
            $request->input('notes'),
            Auth::id()
        );

        return redirect()
            ->back()
            ->with('success', __('donations.contribution_created_success'));
    }

    /**
     * Bulk generate obligations for an active committee for a specified month.
     */
    public function bulkGenerate(BulkGenerateMonthlyContributionRequest $request): RedirectResponse
    {
        $committee = Committee::findOrFail($request->input('committee_id'));

        $result = $this->contributionService->generateForCommittee(
            $committee,
            $request->input('contribution_month'),
            (float) $request->input('default_expected_amount'),
            Auth::id()
        );

        return redirect()
            ->back()
            ->with('success', __('donations.bulk_contributions_generated', [
                'created' => $result['created'],
                'skipped' => $result['skipped'],
            ]));
    }

    /**
     * Waive a monthly contribution obligation.
     */
    public function waive(Request $request, MonthlyCommitteeContribution $contribution): RedirectResponse
    {
        $request->validate([
            'waiver_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $this->contributionService->waiveContribution($contribution, $request->waiver_reason, Auth::id());

        return redirect()
            ->back()
            ->with('success', __('donations.contribution_waived_success'));
    }
}
