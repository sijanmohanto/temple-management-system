<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\CollectionPayment;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Models\JournalEntry;
use App\Services\FinancialReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinancialDashboardController extends Controller
{
    public function __construct(
        protected FinancialReportService $reportService
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('view_finance_dashboard');

        // Balances
        $cashAccount = FinancialAccount::findByCode(FinancialAccount::CODE_CASH);
        $cashBalance = $cashAccount ? $cashAccount->calculateBalance() : 0.00;

        $bankAccounts = FinancialAccount::where('account_type', FinancialAccount::TYPE_ASSET)
            ->where('account_code', 'like', '102%')
            ->get();
        $bankBalance = $bankAccounts->sum(fn ($acc) => $acc->calculateBalance());

        $mfsAccounts = FinancialAccount::where('account_type', FinancialAccount::TYPE_ASSET)
            ->where('account_code', 'like', '103%')
            ->get();
        $mfsBalance = $mfsAccounts->sum(fn ($acc) => $acc->calculateBalance());

        $totalTreasury = $cashBalance + $bankBalance + $mfsBalance;

        // Current month bounds
        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

        $monthlyStatement = $this->reportService->getIncomeExpenseStatement($startOfMonth, $endOfMonth);

        // Pending items
        $pendingExpensesCount = Expense::where('status', Expense::STATUS_PENDING)->count();
        $pendingExpensesAmount = (float) Expense::where('status', Expense::STATUS_PENDING)->sum('amount');

        // Unposted verified collection payments requiring attention
        $unpostedPaymentsCount = CollectionPayment::where('status', CollectionPayment::STATUS_VERIFIED)
            ->whereNull('finance_posted_at')
            ->count();
        $unpostedPaymentsAmount = (float) CollectionPayment::where('status', CollectionPayment::STATUS_VERIFIED)
            ->whereNull('finance_posted_at')
            ->sum('amount');

        // Recent posted journal entries
        $recentJournals = JournalEntry::with(['lines.account'])
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(8)
            ->get();

        return view('finance.dashboard', compact(
            'cashBalance',
            'bankBalance',
            'mfsBalance',
            'totalTreasury',
            'monthlyStatement',
            'pendingExpensesCount',
            'pendingExpensesAmount',
            'unpostedPaymentsCount',
            'unpostedPaymentsAmount',
            'recentJournals'
        ));
    }
}
